<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace Curbstone\IFrame\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UpdateOrderObserver implements ObserverInterface
{

    public function execute(Observer $observer)
    {
        $order = $observer->getData('order');
        if ($order->getPayment()->getMethod() == 'curbstone_iframe') {
        }
    }
}
