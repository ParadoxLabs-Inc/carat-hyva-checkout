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

namespace ParadoxLabs\CaratHyvaCheckout\Block\Customer;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use ParadoxLabs\Carat\Model\Config\Config;

/**
 * Payment pane block for the Hyva customer payment-options add/edit form.
 *
 * Rendered as the 'payment_pane' child of the shared TokenBaseHyvaCheckout form scaffold; supplies
 * the Commerce Hub Checkout SDK transport config (SDK URL, environment, credential endpoint) the
 * pane template needs to mount the hosted payment fields and tokenize the entered card.
 */
class PaymentPane extends Template
{
    /**
     * @param Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly Config $config,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get the Carat Checkout client SDK URL.
     */
    public function getSecureUrl(): string
    {
        return $this->config->getSdkUrl();
    }

    /**
     * Get the Commerce Hub environment code for the client SDK.
     */
    public function getEnvironment(): string
    {
        return $this->config->getEnvironment();
    }

    /**
     * Get the URL that mints one-time credentials + a payment session for tokenization.
     */
    public function getParamUrl(): string
    {
        return $this->getUrl('pdl_carat/secure/getParams');
    }
}
