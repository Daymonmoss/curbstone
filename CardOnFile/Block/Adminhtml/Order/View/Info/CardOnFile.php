<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Curbstone\CardOnFile\Block\Adminhtml\Order\View\Info;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * @api
 * @since 100.0.2
 */
class CardOnFile extends Template
{

    protected OrderRepositoryInterface $orderRepository;

    /**
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    /**
     * @return string
     */
    public function getPayment()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $order = $this->orderRepository->get($orderId);
        $methodCode = $order->getPayment()->getMethod();
        if ($methodCode === 'curbstone_iframe' && !empty($methodCode)) {
            $orderPaymentData = $order->getPayment()->getAdditionalInformation();
            if (!empty($orderPaymentData['raw_details_info']['MFCARD'])) {
                $orderPaymentCard = $orderPaymentData['raw_details_info']['MFCARD'];
                $orderPaymentInfo = "card: " . $orderPaymentCard . ".";
            } else {
                $orderPaymentCard = array_key_exists('card', $orderPaymentData)
                                                      ? $orderPaymentData['card']
                                                      : print_r($orderPaymentData);
                $orderPaymentInfo = "stored card: " .$orderPaymentCard. ".";
            }
        } else {
            $orderPaymentData = $order->getPayment()->getCcLast4();
            if (!empty($orderPaymentData)) {
                $orderPaymentInfo = "card: " . $orderPaymentData . ".";
            } else {
                $orderPaymentInfo = "invoice.";
            }
        }
        return  $orderPaymentInfo;
    }
}
