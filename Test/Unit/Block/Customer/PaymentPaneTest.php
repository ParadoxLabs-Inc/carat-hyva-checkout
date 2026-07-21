<?php

declare(strict_types=1);

namespace ParadoxLabs\CaratHyvaCheckout\Test\Unit\Block\Customer;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\UrlInterface;
use ParadoxLabs\Carat\Model\Config\Config;
use ParadoxLabs\CaratHyvaCheckout\Block\Customer\PaymentPane;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Hyva payment-options pane block
 */
class PaymentPaneTest extends TestCase
{
    private PaymentPane $block;
    private Config|MockObject $config;
    private UrlInterface|MockObject $urlBuilder;

    protected function setUp(): void
    {
        $this->config     = $this->createMock(Config::class);
        $this->urlBuilder = $this->createMock(UrlInterface::class);

        $context = $this->createMock(Context::class);
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder);

        $this->block = new PaymentPane($context, $this->config);
    }

    public function testGetSecureUrlDelegatesToConfig(): void
    {
        $this->config->method('getSdkUrl')->willReturn('https://sdk.example.test/checkout.js');

        $this->assertSame('https://sdk.example.test/checkout.js', $this->block->getSecureUrl());
    }

    public function testGetEnvironmentDelegatesToConfig(): void
    {
        $this->config->method('getEnvironment')->willReturn('CERT');

        $this->assertSame('CERT', $this->block->getEnvironment());
    }

    public function testGetParamUrlBuildsSecureGetParamsRoute(): void
    {
        $this->urlBuilder->expects($this->once())
            ->method('getUrl')
            ->with('pdl_carat/secure/getParams')
            ->willReturn('https://store.example.test/pdl_carat/secure/getParams');

        $this->assertSame(
            'https://store.example.test/pdl_carat/secure/getParams',
            $this->block->getParamUrl()
        );
    }
}
