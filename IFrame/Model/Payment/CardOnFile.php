<?php
/* Copr. 2018 Curbstone Corporation MLP V0.92 */
/* ...\Curbstone\iFrame\Model\Payment\CurbstonePayload.php  */
namespace Curbstone\IFrame\Model\Payment;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

class CardOnFile
{
    protected $request;
    protected $scopeConfig;
    /**
     * @var UrlInterface
     */
    protected $_urlInterface;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Http $request,
        UrlInterface $urlInterface
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->_urlInterface = $urlInterface;
    }

    public function convertAuthorizeData($response, $customer) {
        $payload = array();
        $payload['MFDSIT'] = 1;
        $payload['MFDSIK'] = $this->scopeConfig->getValue('payment/curbstone_iframe/dsi_key', ScopeInterface::SCOPE_STORE);
        $payload['MFCUST'] = $this->scopeConfig->getValue('payment/curbstone_iframe/customer_number', ScopeInterface::SCOPE_STORE);
        $payload['MFMRCH'] = $this->scopeConfig->getValue('payment/curbstone_iframe/merchant_code', ScopeInterface::SCOPE_STORE); // Curbstone merchant code
        $payload['MFUSER'] = $customer->getFirstname(). ' ' . $customer->getLastname();
        $payload['MFTYPE'] = $this->scopeConfig->getValue('payment/curbstone_iframe/txn_type', ScopeInterface::SCOPE_STORE);
        $payload['MFTYP2'] = $this->scopeConfig->getValue('payment/curbstone_iframe/txn_type2', ScopeInterface::SCOPE_STORE);
        $payload['MFMETH'] = $this->scopeConfig->getValue('payment/curbstone_iframe/entry_method', ScopeInterface::SCOPE_STORE);
        $payload['MFREFR'] = '';
        $payload['MFAMT1'] = 1.01;
        $payload['MFKEYP'] = '';
        $payload['MFCARD'] = $response['cc_number'];
        $payload['MFEDAT'] = sprintf("%02d", $response['cc_exp_month']).substr($response['cc_exp_year'], -2);
        $payload['MFCVV2'] = $response['cc_cid'];
        $payload['MFNAME'] = $customer->getFirstname(). ' ' . $customer->getLastname();
        $payload['MFADD1'] = '';
        $payload['MFADD2'] = '';
        $payload['MFCITY'] = '';
        $payload['MFSTAT'] = '';
        $payload['MFZIPC'] = '';
        $payload['MFAMT2'] = 0.02;
        $payload['MFDSTZ'] = '';
        $payload['MFLTXF'] = $this->scopeConfig->getValue('payment/curbstone_iframe/local_tax', ScopeInterface::SCOPE_STORE);
        $payload['MFORDR'] = '';
        $payload['MFUSD1'] = '';
        $payload['MFUSD2'] = '';
        $payload['MFUSD3'] = '';
        $payload['MFUSD4'] = '';
        $payload['MFUSD5'] = '';
        $payload['MFUSD6'] = '';
        $payload['MFUSD7'] = '';
        $payload['MFUSDA'] = '';
        $payload['MFUSDB'] = '';
        $payload['MFUSDC'] = '';
        $payload['MFUKEY'] = '';
        return $payload;
    }

    public function convertVerifyData($response, $customer) {
        $response['MFDSIK'] = $this->scopeConfig->getValue('payment/curbstone_iframe/dsi_key', ScopeInterface::SCOPE_STORE);
        $response['MFTYPE'] = 'RY';
        $response['MFKEYP'] = $response['MFUKEY'];
        $response['MFUKEY'] = '';
        $response['MFCARD'] = '';
        $response['MFEDAT'] = '';
        $response['MFCVV2'] = '';
        return $response;
    }
}
