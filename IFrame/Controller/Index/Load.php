<?php
/* Copr. 2018 Curbstone Corporation MLP V0.92 */

namespace Curbstone\IFrame\Controller\Index;

use Curbstone\IFrame\Model\Payment\CurbstonePayload;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Curbstone\IFrame\Helper\CurbstoneLog;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

class Load implements ActionInterface
{
    protected JsonFactory $resultJsonFactory;
    protected CheckoutSession $checkoutSession;
    protected ScopeConfigInterface $scopeConfig;
    protected CurbstonePayload $curbstonePayload;
    protected CurbstoneLog $payLog;

    public function __construct(
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        CurbstonePayload $curbstonePayload,
        CurbstoneLog $payLog
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->curbstonePayload = $curbstonePayload;
        $this->payLog = $payLog;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
        $billingAddress = $quote->getBillingAddress();
        $PLP_API_URL = $this->scopeConfig->getValue('payment/curbstone_iframe/api_url', ScopeInterface::SCOPE_STORE);
        $payload = $this->curbstonePayload->payLoad($billingAddress,$quote);
        $this->payLog->writePaylog("Request load iFrame Data:");
        $this->payLog->writePaylog(print_r($payload, true));
        $payload_string = '';
        foreach ($payload as $key => $value) {
            $payload_string .= $key . '=' . urlencode($value) . '&';
        }
        rtrim($payload_string, '&');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $PLP_API_URL . '?action=init');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload_string);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        if ($result == false) {
            echo 'Curl error #: ' . curl_errno($ch) . "<br>";
            echo 'Curl error: ' . curl_error($ch) . "<br>";
        }
        $result = json_decode($result, true);
        $this->payLog->writePaylog("Response load iFrame Data:");
        $this->payLog->writePaylog(print_r($result, true));
        $transaction_token = $result['MFSESS'];

        $PLP_TXN_URL = $PLP_API_URL . '?MFSESS=' . $transaction_token;
        $result = $this->resultJsonFactory->create();

        $result->setData(['url' => $PLP_TXN_URL]);
        return $result;
    }
}
