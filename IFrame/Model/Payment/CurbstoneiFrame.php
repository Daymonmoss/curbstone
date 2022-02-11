<?php
/* Copr. 2018 Curbstone Corporation MLP V0.92 */

namespace Curbstone\IFrame\Model\Payment;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Adapter;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Checkout\Model\CartFactory;
use Magento\Quote\Api\Data\PaymentMethodInterface;


class CurbstoneiFrame implements MethodInterface, PaymentMethodInterface
{

    private CartFactory $cartFactory;
    private Adapter $adapter;
    private InfoInterface $info;


    public function __construct
    (
        Adapter $adapter,
        CartFactory $cartFactory,
        InfoInterFace $info

    ) {
        $this->cartFactory = $cartFactory;
        $this->adapter = $adapter;
        $this->info = $info;

    }

    public function isActive($storeId = null)
    {
        return $this->adapter->isActive($storeId);
    }

    public function isAvailable(CartInterface $quote = null)
    {
        return $this->adapter->isAvailable($quote);
    }

    public function isGateway()
    {
        return $this->adapter->isGateway();
    }

    public function isInitializeNeeded()
    {
        return $this->adapter->isInitializeNeeded();
    }

    public function isOffline()
    {
        return $this->adapter->isOffline();
    }

// ---------------------------------------------------------------------------------------------------------------------

    public function acceptPayment(InfoInterface $payment)
    {
       return $this->adapter->acceptPayment($payment);
    }

    public function assignData(DataObject $data)
    {
        $addData = $this->getInfoInstance()->setAdditionalInformation($data->getData('additional_data'));
        $this->setInfoInstance($addData);
    }

    public function authorize(InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new LocalizedException(__('The authorize action is not available.'));
        }
        try{
            $cart = $this->cartFactory->create();
            $quotePayment = $cart->getQuote()->getPayment();
            $additional = ($quotePayment->getData('additional_information'));
            if(isset($additional['tokenValue']) && $additional['tokenValue'] && $payment->getLastTransId() == null) {
                $payment->setTransactionId($additional['tokenValue']);
                $payment->setLastTransId($additional['tokenValue']);
                $addData = ['vault_gateway_token' => $additional['tokenValue'], 'card' => $additional['cardMasked']];
                $payment->setAdditionalInformation($addData);
            }
        } catch (\Exception $e) {

        }
        return $this;
    }

    public function canAuthorize()
    {
        return $this->adapter->canAuthorize();
    }

    public function cancel(InfoInterface $payment)
    {
        return $this->adapter->cancel($payment);
    }

    public function capture(InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new LocalizedException(__('The capture action is not available.'));
        }
        return $this;
    }

    public function canCapture()
    {
        return $this->adapter->canCapture();
    }

    public function canCaptureOnce()
    {
        return $this->adapter->canCaptureOnce();
    }

    public function canCapturePartial()
    {
        return $this->adapter->canCapturePartial();
    }

    public function canEdit()
    {
        return $this->adapter->canEdit();
    }

    public function denyPayment(InfoInterface $payment)
    {
        return $this->adapter->denyPayment($payment);
    }

    public function fetchTransactionInfo(InfoInterface $payment, $transactionId)
    {
        return $this->adapter->fetchTransactionInfo($payment, $transactionId);
    }

    public function canFetchTransactionInfo()
    {
        return $this->adapter->canFetchTransactionInfo();
    }

    public function initialize($paymentAction, $stateObject)
    {
        return $this->adapter->initialize($paymentAction, $stateObject);
    }

    public function order(InfoInterface $payment, $amount)
    {
        return $this->adapter->order($payment, $amount);

    }

    public function canOrder()
    {
        return $this->adapter->canOrder();
    }

    public function refund(InfoInterface $payment, $amount)
    {
        return  $this->adapter->refund($payment, $amount);
    }

    public function canRefund()
    {
        return $this->adapter->canRefund();
    }

    public function canRefundPartialPerInvoice()
    {
        return $this->adapter->canRefundPartialPerInvoice();
    }

    public function canReviewPayment()
    {
        return $this->adapter->canReviewPayment();
    }

    public function validate()
    {
        return $this->adapter->validate();
    }

    public function void(InfoInterface $payment)
    {
        return $this->adapter->void($payment);
    }

    public function canVoid()
    {
        return $this->adapter->canVoid();
    }

// ---------------------------------------------------------------------------------------------------------------------

    public function canUseCheckout()
    {
        return $this->adapter->canUseCheckout();
    }

    public function canUseInternal()
    {
        return $this->adapter->canUseInternal();
    }

    public function canUseForCountry($country)
    {
        return $this->adapter->canUseForCountry($country);
    }

    public function canUseForCurrency($currencyCode)
    {
        return $this->adapter->canUseForCurrency($currencyCode);
    }

// ---------------------------------------------------------------------------------------------------------------------

    public function getCode()
    {
        return $this->adapter->getCode();
    }

    public function getConfigData($field, $storeId = null)
    {
        return $this->adapter->getConfigData($field, $storeId);
    }

    public function getConfigPaymentAction()
    {
        return $this->adapter->getConfigPaymentAction();
    }

    public function getFormBlockType()
    {
         return "Magento\Payment\Block\Form";
    }

    public function getInfoBlockType()
    {
         return "Magento\Payment\Block\Info";
    }

    public function getInfoInstance()
    {
        return $this->info;
    }

    public function setInfoInstance(InfoInterface $info)
    {
        $this->info = $info;
    }

    public function getStore()
    {
        return $this->adapter->getStore();
    }

    public function setStore($storeId)
    {
        $this->adapter->setStore($storeId);
    }

    public function getTitle()
    {
        return $this->adapter->getTitle();
    }
}
