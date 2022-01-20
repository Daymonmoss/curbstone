<?php
namespace Curbstone\IFrame\Observer;

use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;

class PaymentAdditionalDataAssignObserver extends AbstractDataAssignObserver
{
    const TOKEN_VALUE_KEY = 'tokenValue';
    const MASKED_VALUE_KEY = 'cardMasked';

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData) || !isset($additionalData[self::TOKEN_VALUE_KEY])) {
            return;
        }
        $paymentInfo = $this->readPaymentModelArgument($observer);
        $paymentInfo->setAdditionalInformation(
            self::TOKEN_VALUE_KEY,
            $additionalData[self::TOKEN_VALUE_KEY]
        );
        $paymentInfo->setAdditionalInformation(
            self::MASKED_VALUE_KEY,
            $additionalData[self::MASKED_VALUE_KEY]
        );
    }
}
