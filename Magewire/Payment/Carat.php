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

use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magewirephp\Magewire\Component\Form;
use ParadoxLabs\CaratHyvaCheckout\ViewModel\PaymentForm;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterface;
use ParadoxLabs\TokenBase\Block\Form\Cc;
use ParadoxLabs\TokenBase\Model\Card;
use Rakit\Validation\Validator;
use Throwable;

/**
 * Magewire component for the Carat (Fiserv Commerce Hub) hosted payment fields form.
 *
 * Card entry happens client-side in the Commerce Hub Checkout SDK hosted-field iframes (see
 * scripts.phtml); this component owns the stored-card list and the place-order evaluation. The
 * tokenized payment session is amount-agnostic — the charge amount is set server-side at sale —
 * so no quote-total sync with the browser is needed.
 */
class Carat extends Form implements EvaluationInterface
{
    protected const METHOD_CODE = 'paradoxlabs_carat';

    /* Public component properties */
    public $storedCards = [];

    /* Protected property validation rule map */
    protected $rules
        = [
            'storedCards' => 'array',
        ];

    /**
     * @param \Rakit\Validation\Validator $validator
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     * @param \ParadoxLabs\CaratHyvaCheckout\ViewModel\PaymentForm $formViewModel
     */
    public function __construct(
        Validator $validator,
        protected CheckoutSession $checkoutSession,
        protected CardRepositoryInterface $cardRepository,
        protected PaymentForm $formViewModel,
    ) {
        parent::__construct($validator);
    }

    /**
     * Initialize component data on update
     */
    public function boot(): void
    {
        $this->loadSelectedCard();
    }

    /**
     * @throws LocalizedException
     */
    public function mount(): void
    {
        $this->loadStoredCards();
    }

    /**
     * Update component selected card based on the quote's assigned stored card
     *
     * @throws LocalizedException
     */
    protected function loadSelectedCard(): void
    {
        $payment = $this->getQuote()->getPayment();

        if ($payment->getData('tokenbase_id') === null) {
            return;
        }

        try {
            $card = $this->cardRepository->getById($payment->getData('tokenbase_id'));
        } catch (Throwable) {
            // The assigned card no longer exists (deleted since assignment); fall back to
            // new-card entry rather than failing every Magewire request for the component.
            return;
        }

        if ($card->getMethod() === static::METHOD_CODE
            && (int)$card->getCustomerId() === (int)$this->getQuote()->getCustomerId()) {
            $this->addStoredCardToList($card);
        }
    }

    /**
     * Get the current user's active quote
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getQuote(): CartInterface
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Determine whether checkout completion is allowed
     */
    public function evaluateCompletion(EvaluationResultFactory $resultFactory): EvaluationResultInterface
    {
        $validationError = $resultFactory->createErrorMessage();
        $validationError->withMessage(
            (string)__('There\'s an issue with your payment details. Please check the payment form.')
        );
        $validationError->withVisibilityDuration(5000);

        $validation = $resultFactory->createValidation('validate' . static::METHOD_CODE);
        $validation->withFailureResult($validationError);

        return $validation;
    }

    /**
     * Get the active payment method form block
     *
     * @throws LocalizedException
     */
    protected function getFormBlock(): Cc
    {
        return $this->formViewModel->getFormBlock(static::METHOD_CODE);
    }

    /**
     * @throws LocalizedException
     */
    protected function loadStoredCards(): void
    {
        /** @var Card $card */
        foreach ($this->getFormBlock()->getStoredCards() as $card) {
            $this->addStoredCardToList($card);
        }
    }

    /**
     * Add the given card to the component stored-card list
     */
    protected function addStoredCardToList(CardInterface $card): void
    {
        $card = $card->getTypeInstance();
        $hash = (string)$card->getHash();

        $this->storedCards[$hash] = [
            'hash' => $hash,
            'label' => $card->getLabel(),
            'type' => $card->getType(),
        ];
    }
}
