<?php

declare(strict_types=1);

namespace ParadoxLabs\CaratHyvaCheckout\Test\Unit\Magewire\Payment;

use Hyva\Checkout\Model\Magewire\Payment\DefaultOrderData;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use ParadoxLabs\CaratHyvaCheckout\Magewire\Payment\PlaceOrderService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Hyva Checkout PlaceOrderService
 */
class PlaceOrderServiceTest extends TestCase
{
    private PlaceOrderService $service;
    private CartManagementInterface|MockObject $cartManagement;
    private Quote|MockObject $quote;
    private QuotePayment|MockObject $payment;

    protected function setUp(): void
    {
        $this->cartManagement = $this->createMock(CartManagementInterface::class);
        $this->payment = $this->createMock(QuotePayment::class);

        $this->quote = $this->createMock(Quote::class);
        $this->quote->method('getPayment')->willReturn($this->payment);
        $this->quote->method('getId')->willReturn(7);

        $this->service = new PlaceOrderService(
            $this->cartManagement,
            new DefaultOrderData(),
        );
    }

    public function testPlaceOrderImportsWhitelistedPaymentData(): void
    {
        $this->service->getData()->setData([
            'payment' => [
                'session_id' => 'chsession-abc123',
                'card_id' => '',
                'cc_cid' => '',
                'save' => 1,
            ],
        ]);

        $this->payment->expects($this->once())
            ->method('importData')
            ->with([
                PaymentInterface::KEY_METHOD => 'paradoxlabs_carat',
                PaymentInterface::KEY_ADDITIONAL_DATA => [
                    'session_id' => 'chsession-abc123',
                    'card_id' => '',
                    'cc_cid' => '',
                    'save' => 1,
                ],
            ]);

        $this->cartManagement->expects($this->once())
            ->method('placeOrder')
            ->with(7, $this->payment)
            ->willReturn('42');

        $this->assertSame(42, $this->service->placeOrder($this->quote));
    }

    public function testPlaceOrderStripsUnknownKeysAndForcesMethod(): void
    {
        $this->service->getData()->setData([
            'payment' => [
                'method' => 'checkmo',
                'session_id' => 'chsession-abc123',
                'tokenbase_id' => '999',
                'carat_session_id' => 'chsession-evil',
                'transaction_id' => 'txn-evil',
                'save' => 0,
            ],
        ]);

        $this->payment->expects($this->once())
            ->method('importData')
            ->with([
                PaymentInterface::KEY_METHOD => 'paradoxlabs_carat',
                PaymentInterface::KEY_ADDITIONAL_DATA => [
                    'session_id' => 'chsession-abc123',
                    'save' => 0,
                ],
            ]);

        $this->cartManagement->method('placeOrder')->willReturn('100');

        $this->assertSame(100, $this->service->placeOrder($this->quote));
    }

    public function testPlaceOrderPassesStoredCardWithCvv(): void
    {
        $this->service->getData()->setData([
            'payment' => [
                'card_id' => 'a1b2c3hash',
                'cc_cid' => '123',
                'save' => 1,
            ],
        ]);

        $this->payment->expects($this->once())
            ->method('importData')
            ->with([
                PaymentInterface::KEY_METHOD => 'paradoxlabs_carat',
                PaymentInterface::KEY_ADDITIONAL_DATA => [
                    'card_id' => 'a1b2c3hash',
                    'cc_cid' => '123',
                    'save' => 1,
                ],
            ]);

        $this->cartManagement->method('placeOrder')->willReturn('55');

        $this->assertSame(55, $this->service->placeOrder($this->quote));
    }

    public function testPlaceOrderWithNoPaymentDataImportsEmptyAdditionalData(): void
    {
        $this->service->getData()->setData([]);

        $this->payment->expects($this->once())
            ->method('importData')
            ->with([
                PaymentInterface::KEY_METHOD => 'paradoxlabs_carat',
                PaymentInterface::KEY_ADDITIONAL_DATA => [],
            ]);

        $this->cartManagement->method('placeOrder')->willReturn('5');

        $this->assertSame(5, $this->service->placeOrder($this->quote));
    }
}
