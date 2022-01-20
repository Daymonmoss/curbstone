<?php

namespace Curbstone\IFrame\Plugin;

use Magento\Sales\Model\Order\Payment\State\CommandInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Curbstone\IFrame\Model\Ui\ConfigProvider;
use Magento\Payment\Model\MethodInterface;

class CommandInterfacePlugin
{
    public function aroundExecute(CommandInterface $subject, \Closure $proceed, OrderPaymentInterface $payment, $amount, OrderInterface $order)
    {
        $result = $proceed($payment, $amount, $order);
        if ($payment->getMethod() == ConfigProvider::CODE && $payment->getAnetTransMethod() == MethodInterface::ACTION_AUTHORIZE) {
            $orderStatus = Order::STATE_NEW;
            if ($orderStatus && $order->getState() == Order::STATE_PROCESSING) {
                $order->setStatus($orderStatus);
            }
        }

        return $result;
    }
}
