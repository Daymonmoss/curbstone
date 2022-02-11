<?php
/* Copr. 2018 Curbstone Corporation MLP V0.92 */
/* ...\Curbstone\CardOnFile\Model\Payment\CurbstonePayload.php  */
namespace Curbstone\CardOnFile\Model\Payment;

use Curbstone\IFrame\Model\Payment\CurbstonePayload;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

class CardOnFilePayload extends CurbstonePayload
{
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Http $request,
        UrlInterface $urlInterface,
        RegionCollection $regionCollection,
        FormKey $formKey
    ) {
        parent::__construct($scopeConfig, $request, $urlInterface, $regionCollection, $formKey);
    }

    public function payLoad($billingAddress, $customer)
    {
        $regionCode = $this->regionCollection->getItemById($billingAddress->getRegionId());
        $amount = "1.01";
        $payload = array(
            'MFADD1' => isset($billingAddress->getStreet()[0]) ? $billingAddress->getStreet()[0] : '',
            'MFADD2' => isset($billingAddress->getStreet()[1]) ? $billingAddress->getStreet()[1] : '',
            'MFAMT1' => $amount,
            'MFAMT2' => "0.02",
            'MFCITY' => $billingAddress->getCity(),
            'MFDSTZ' => $billingAddress->getPostcode(),
            'MFLTXF' => $this->scopeConfig->getValue('payment/curbstone_iframe/local_tax', ScopeInterface::SCOPE_STORE), // Local Tax Flag
            'MFMETH' => $this->scopeConfig->getValue('payment/curbstone_iframe/entry_method', ScopeInterface::SCOPE_STORE), // Card entry method
            'MFMRCH' => $this->scopeConfig->getValue('payment/curbstone_iframe/merchant_code', ScopeInterface::SCOPE_STORE), // Curbstone merchant code
            'MFNAME' => $customer->getFirstname() . " " . $customer->getLastname(),
            'MFORDR' => $billingAddress->getTelephone(), // order number
            'MFREFR' => $customer->getId(), // Invoice number
            'MFSTAT' => $regionCode,
            'MFTYP2' => $this->scopeConfig->getValue('payment/curbstone_iframe/payment_action', ScopeInterface::SCOPE_STORE) !== 'authorize_capture' ? 'PA' : 'SA', // Request Authorization,
            'MFTYPE' => $this->scopeConfig->getValue('payment/curbstone_iframe/txn_type', ScopeInterface::SCOPE_STORE),
            'MFUSD1' => '',
            'MFUSD2' => '',
            'MFUSD3' => '',
            'MFUSD4' => '',
            'MFUSD5' => '',
            'MFUSD6' => '',
            'MFUSD7' => '',
            'MFUSD8' => '',
            'MFUSDA' => '',
            'MFUSDB' => '',
            'MFUSDC' => '',
            'MFUSER' => '',
            'MFZIPC' => $billingAddress->getPostcode(),
            'MPCUST' => $customer->getId() ? $customer->getId() : '',
            'MPTRGT' => $this->urlInterface->getUrl('curbstone_cardonfile/index/savecard').'?form_key='.$this->getFormKey(),
            'MFCUST' => $this->scopeConfig->getValue('payment/curbstone_iframe/customer_number', ScopeInterface::SCOPE_STORE),
            'SVRTGT' => 'SANDBOX',
        );

        return $payload;
    }
}
