<?php
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

use Magento\Framework\Component\ComponentRegistrar;

$dir = __DIR__;
$ds  = DIRECTORY_SEPARATOR;
if (isset($file) && strpos((string)$file, $ds . 'vendor' . $ds . 'composer' . $ds . '..') === false) {
    $dir = dirname((string)$file);
}

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'ParadoxLabs_CaratHyvaCheckout',
    $dir
);
