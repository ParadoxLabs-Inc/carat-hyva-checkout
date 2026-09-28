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

namespace ParadoxLabs\CaratHyvaCheckout\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use ParadoxLabs\Carat\Model\Config\CheckoutProvider;
use ParadoxLabs\Carat\Model\Config\Config;

class CheckoutTemplate extends Template
{
    /**
     * Constructor
     *
     * @param Context $context
     * @param CheckoutProvider $configProvider
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected readonly CheckoutProvider $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get payment method code for the active/relevant method
     *
     * @return string
     */
    public function getMethodCode(): string
    {
        return $this->getData('method_code') ?? Config::CODE;
    }

    /**
     * Get payment form config object
     *
     * @return array
     */
    public function getConfig(): array
    {
        $config = $this->configProvider->getConfig();

        return $config['payment'][ $this->getMethodCode() ] ?? [];
    }
}
