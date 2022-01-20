<?php
/* Copr. 2018 Curbstone Corporation MLP V0.92 */
/* ...\Curbstone\iFrame\Model\Payment\CurbstonePayload.php  */
namespace Curbstone\IFrame\Model\Payment;

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

class CurbstonePayload
{
    protected $request;
    protected $scopeConfig;
    protected $_regionFactory;
    /**
     * @var UrlInterface
     */
    protected $_urlInterface;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Http $request,
        UrlInterface $urlInterface,
        RegionFactory $regionFactory,
        FormKey $formKey
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->_urlInterface = $urlInterface;
        $this->_regionFactory = $regionFactory;
        $this->formKey = $formKey;
    }

    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    public function payLoad(
        $billingAddress, $quote
    ) {
        $customer = $quote->getCustomer();
        if(!$quote->getReservedOrderId()) {
            $quote->reserveOrderId();
        }
        $regionCode = '';
        if (is_numeric($billingAddress->getRegionId())) {
            $region = $this->_regionFactory->create()->load($billingAddress->getRegionId() );
            $regionCode =$region->getCode();
        }
        $MPCUSF = $this->request->getParam('quoteEmail') ? 'quoteEmail='.$this->request->getParam('quoteEmail') : '' .'&cardOnFile='.$this->request->getParam('cardOnFile');
        $payload = array(
            'MFADD1' => isset($billingAddress->getStreet()[0]) ? $billingAddress->getStreet()[0] : '',
            'MFADD2' => isset($billingAddress->getStreet()[1]) ? $billingAddress->getStreet()[1] : '',
            'MFAMT1' => $quote->getGrandTotal(), // purchase
            'MFAMT2' => $quote->getShippingAddress()->getData('tax_amount'), // Tax
            'MFCITY' => $billingAddress->getCity(),
            'MFDSTZ' => $billingAddress->getPostcode(),
            'MFLTXF' => $this->scopeConfig->getValue('payment/curbstone_iframe/local_tax', ScopeInterface::SCOPE_STORE), // Local Tax Flag
            'MFMETH' => $this->scopeConfig->getValue('payment/curbstone_iframe/entry_method', ScopeInterface::SCOPE_STORE), // Card entry method
            'MFMRCH' => $this->scopeConfig->getValue('payment/curbstone_iframe/merchant_code', ScopeInterface::SCOPE_STORE), // Curbstone merchant code
            'MFNAME' => $billingAddress->getFirstname() . " " .$billingAddress->getLastname(),
            'MFORDR' => $quote->getReservedOrderId(), // order number
            'MFREFR' => $quote->getId(), // Invoice number
            'MFSTAT' => $regionCode,
            'MFTYP2' => $this->scopeConfig->getValue('payment/curbstone_iframe/payment_action', ScopeInterface::SCOPE_STORE) !== 'authorize_capture' ? 'PA' : 'SA', // Request Authorization,
            'MFTYPE' => $this->scopeConfig->getValue('payment/curbstone_iframe/txn_type', ScopeInterface::SCOPE_STORE), // pre-authorization, or SA, sale,
            'MFUKEY' => '',
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
            'MPCUSF' => $MPCUSF.'&reservedOrderId='.$quote->getReservedOrderId().'&form_key='.$this->getFormKey(),
            'MPCUST' => $customer->getId() ? $customer->getId() : '',
            'MPTRGT' => $this->_urlInterface->getUrl('curbstone_iframe/index/callback'),
            'MFCUST' => $this->scopeConfig->getValue('payment/curbstone_iframe/customer_number', ScopeInterface::SCOPE_STORE)
        );
        return $payload;
    }
}
