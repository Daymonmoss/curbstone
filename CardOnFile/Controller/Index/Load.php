<?php
/* Copr. 2018 Curbstone Corporation MLP V0.92 */

namespace Curbstone\CardOnFile\Controller\Index;

use Curbstone\IFrame\Helper\CurbstoneLog;
use Curbstone\IFrame\Model\Payment\CurbstonePayload;
use Curbstone\CardOnFile\Model\Payment\CardOnFilePayload;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

class Load extends \Curbstone\IFrame\Controller\Index\Load
{
    protected Session $customerSession;
    protected CustomerRepositoryInterface $customerRepository;
    protected AddressRepositoryInterface $addressRepository;
    private CardOnFilePayload $payLoad;

    public function __construct(
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        CurbstonePayload $curbstonePayload,
        CardOnFilePayload $payLoad,
        CurbstoneLog $payLog,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository
    ) {
        parent::__construct($resultJsonFactory, $checkoutSession, $scopeConfig, $curbstonePayload, $payLog);
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->payLoad = $payLoad;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute()
    {
        $customer = $this->customerRepository->getById($this->customerSession->getCustomer()->getId());
        $customerBillAddress = $this->addressRepository->getById($customer->getDefaultBilling());
        $customerShipAddress = $this->addressRepository->getById($customer->getDefaultShipping());
        $billingAddress = $customerBillAddress ?: $customerShipAddress;
        $PLP_API_URL = $this->scopeConfig->getValue('payment/curbstone_iframe/api_url', ScopeInterface::SCOPE_STORE);
        $payload = $this->payLoad->payLoad($billingAddress, $customer);
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
