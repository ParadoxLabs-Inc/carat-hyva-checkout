<?php

declare(strict_types=1);

/**
 * ParadoxLabs, Inc.
 * https://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  https://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <info@paradoxlabs.com>
 * @license     https://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\CaratHyvaCheckout\Magewire\Payment;

use Exception;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Hyva\Checkout\Model\Magewire\Payment\AbstractPlaceOrderService;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magewirephp\Magewire\Component;
use ParadoxLabs\Carat\Model\Config\Config;

class PlaceOrderService extends AbstractPlaceOrderService
{
    /**
     * @var Exception|null
     */
    protected ?Exception $placeOrderException = null;
    /**
     * Checkout SDK (SDCv2) additional_data contract: exactly one of {session_id, card_id}
     * populated per submit, plus the stored-card CCV and the save-card flag.
     */
    private const ALLOWED_KEYS
        = [
            'card_id' => null,
            'session_id' => null,
            'cc_cid' => null,
            'save' => null,
        ];

    /**
     * Assign the client payment data to the quote payment, then place the order.
     *
     * importData() (not addData()) so the payment_method_assign_data observer chain runs — that is
     * what copies session_id into additional_information (carat_session_id) and resolves card_id
     * into tokenbase_id.
     *
     * No re-staging is needed for the second importData() that QuoteManagement::placeOrder() runs
     * against the same payment instance: Carat's assign observer treats a bare re-import (no
     * card_id or session_id in the data) as a no-op — a stored-card assign clears
     * carat_session_id at first import, a session assign stores it, and the re-import disturbs
     * neither the stored session nor an assigned tokenbase_id.
     *
     * @throws CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function placeOrder(Quote $quote): int
    {
        $paymentData = (array)$this->getData()->getPayment();

        // Only pass through known allowed values, to prevent parameter injection
        $knownPaymentData = array_intersect_key(
            $paymentData,
            self::ALLOWED_KEYS,
        );

        $quote->getPayment()->importData([
            PaymentInterface::KEY_METHOD => Config::CODE,
            PaymentInterface::KEY_ADDITIONAL_DATA => $knownPaymentData,
        ]);

        return parent::placeOrder($quote);
    }

    /**
     * Accept a place-order failure instead of rethrowing.
     *
     * The default rethrow aborts the Magewire request with a bare {message, code} JSON response:
     * the order:place:{method}:error browser events the processor queued before calling this are
     * only serialized into response effects at dehydrate, so they are silently discarded — the
     * client-side session invalidation and CVV scrub never run — and in production mode the
     * customer sees a generic "page refresh" dialog instead of the decline message.
     *
     * Swallowing the exception lets the request complete normally, which delivers the queued
     * error events; evaluateCompletion() surfaces the real (sanitized) message through the
     * standard messenger, and canRedirect() blocks the unconditional success-page redirect the
     * processor would otherwise push for the failed attempt.
     */
    public function handleException(Exception $exception, Component $component, Quote $quote): void
    {
        $this->placeOrderException = $exception;
    }

    /**
     * Report the place-order outcome: default success behavior when an order was placed, an
     * error message with the failure reason otherwise.
     *
     * Message sanitization mirrors the processor's event detail: a LocalizedException message is
     * safe to show; anything else gets the generic text. Never returns the parent's unconditional
     * success for a failed attempt.
     */
    public function evaluateCompletion(
        EvaluationResultFactory $resultFactory,
        int|null $orderId = null,
    ): EvaluationResultInterface {
        if ($this->placeOrderException === null) {
            return parent::evaluateCompletion($resultFactory, $orderId);
        }

        $message = $this->placeOrderException instanceof LocalizedException
            ? $this->placeOrderException->getMessage()
            : (string)__('Something went wrong while processing your order. Please try again.');

        $errorMessage = $resultFactory->createErrorMessage();
        $errorMessage->withMessage($message);
        $errorMessage->withVisibilityDuration(7500);

        return $errorMessage;
    }

    /**
     * Block the processor's success-page redirect after a failed attempt, keeping the customer
     * on checkout in a retryable state.
     */
    public function canRedirect(): bool
    {
        return $this->placeOrderException === null;
    }
}
