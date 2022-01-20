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
        $paymentMethod = $payment->getMethod();
        $paymentTransactionType = $payment->getMethodInstance()->getConfigPaymentAction();
        if ($paymentMethod == ConfigProvider::CODE && $paymentTransactionType == MethodInterface::ACTION_AUTHORIZE) {
            if ($order->getState() || $order->getStatus() == Order::STATE_PROCESSING) {
                $order->setStatus(Order::STATE_NEW);
                $order->setState(Order::STATE_NEW);
            }
        }

        return $result;
    }
}
