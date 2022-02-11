<?php
/* Copr. 2018 Curbstone Corporation MLP V0.92 */

namespace Curbstone\IFrame\Controller\Index;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Model\Session\SuccessValidator;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Vault\Model\PaymentTokenRepository;
use Magento\Framework\Message\ManagerInterface;
use Curbstone\IFrame\Helper\RequestDataBuilder;
use Curbstone\IFrame\Helper\CurbstoneLog;
use Curbstone\IFrame\Model\Ui\ConfigProvider;


class Callback implements ActionInterface
{
    private RequestDataBuilder $requestDataBuilder;
    private CartRepositoryInterface $quoteRepository;
    private QuoteManagement $quoteManagement;
    private ScopeConfigInterface $scopeConfig;
    private CurbstoneLog $payLog;
    private PaymentTokenFactoryInterface $paymentTokenFactory;
    private EncryptorInterface $encryptor;
    private CheckoutSession $checkoutSession;
    private ManagerInterface $messageManager;
    private RequestInterface $request;
    private ResponseInterface $response;
    private RedirectInterface $redirect;
    private SuccessValidator $successValidator;
    private BuilderInterface $transactionBuilder;
    private OrderPaymentRepositoryInterface $orderPaymentRepository;
    private PaymentTokenRepository $paymentTokenRepository;
    private CustomerSession $customerSession;

    public function __construct(
        RequestDataBuilder $requestDataBuilder,
        CheckoutSession $checkoutSession,
        QuoteManagement $quoteManagement,
        CartRepositoryInterface $quoteRepository,
        BuilderInterface $builderInterface,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        ScopeConfigInterface $scopeConfig,
        CurbstoneLog $payLog,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        EncryptorInterface $encryptor,
        PaymentTokenRepository $paymentTokenRepository,
        CustomerSession $customerSession,
        ManagerInterface $messageManager,
        RequestInterface $request,
        ResponseInterface $response,
        RedirectInterface $redirect,
        SuccessValidator $successValidator
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->requestDataBuilder = $requestDataBuilder;
        $this->quoteManagement = $quoteManagement;
        $this->quoteRepository = $quoteRepository;
        $this->transactionBuilder = $builderInterface;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->scopeConfig = $scopeConfig;
        $this->payLog = $payLog;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->encryptor = $encryptor;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->request = $request;
        $this->response = $response;
        $this->redirect = $redirect;
        $this->successValidator = $successValidator;
    }

    public function execute()
    {
        $response = $this->getRequest()->getParams();
        $resultUrl = 'checkout/cart';
        $this->payLog->writePaylog("Response place order Data:");
        $this->payLog->writePaylog(print_r($response, true));
        $cardOnFile = $this->scopeConfig->getValue('payment/curbstone_iframe/store_card', ScopeInterface::SCOPE_STORE);
        if (array_key_exists('MFRTRN', $response)) {
            // Determine if the transaction was successful
            switch ($response['MFRTRN']) {
                case 'UG':
                    if($cardOnFile && isset($response['cardOnFile']) && $response['cardOnFile'] == 'true' && $this->customerSession->isLoggedIn()) {
                        $this->createVaultToken($response);
                    }
                    $this->submitOrder($response, $resultUrl);
                    break;
                case 'UN':
                    $confirmation_msg = 'Authorization declined: ' . $response['MFRTXT'];
                    $this->messageManager->addErrorMessage(__('Payment could not be processed. ') . $confirmation_msg);
                    $this->redirect($resultUrl);
                    break;
                case 'UL':
                default:
                    $confirmation_msg = 'Field Error - code: ' . $response['MFATAL'];
                    $this->messageManager->addErrorMessage(__('Payment could not be processed. ') . $confirmation_msg);
                    $this->redirect($resultUrl);
                    break;
            }
        } else {
            $this->messageManager->addErrorMessage(__('Payment could not be processed.'));
            $this->redirect($resultUrl);
        }
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    protected function submitOrder($response, $resultUrl)
    {
        /** @var Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        if ($this->requestDataBuilder->getCheckoutMethod($quote) === Onepage::METHOD_GUEST) {
            $quote->getBillingAddress()->setEmail($response['quoteEmail']);
            $this->requestDataBuilder->prepareGuestQuote($quote);
        }
        $quote->setPaymentMethod('curbstone_iFrame');
        $quote->setInventoryProcessed(false);
        $quote->save();
        // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => 'curbstone_iframe']);
        $quote->getPayment()->setAdditionalInformation("curbstone_response", $response);
        $quote->getPayment()->setAdditionalInformation("skip_authorize", true);
        if(isset($response['cardOnFile'])) {
            $quote->getPayment()->setAdditionalInformation("card_on_file", $response['cardOnFile']);
        }
        $quote->getPayment()->setLastTransId($response['MFUKEY']);
        $quote->getPayment()->setTransactionId($response['MFUKEY']);

        $quote->collectTotals()->save();
        $this->quoteRepository->save($quote);
        $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
        $this->checkoutSession->setLastQuoteId($quote->getId());
        $this->checkoutSession->clearHelperData();
        if(isset($response['reservedOrderId']) && $response['reservedOrderId']) {
            $quote->setReservedOrderId($response['reservedOrderId']);
        }
        try {
            $order = $this->quoteManagement->submit($quote);

            $payment = $order->getPayment();
            $payment->setTransactionId($response['MFUKEY']);
            $payment->setLastTransId($response['MFUKEY']);
            $payment->setQuotePaymentId($quote->getPayment()->getPaymentId());

            $payment->setAdditionalInformation(['request_id' => $response['MFUKEY']]);
            $payment->setAdditionalData(json_encode($response));

            $this->orderPaymentRepository->save($payment);
            $order->save();

            $this->checkoutSession->setLastOrderId($order->getId());
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->checkoutSession->setLastOrderStatus($order->getStatus());
            $this->createPaymentTransaction($order, $response);

            if (!$this->successValidator->isValid()) {
                $resultUrl = 'checkout/cart';
            }
            $this->messageManager->addSuccessMessage('Your order has been successfully created!');
            $resultUrl = 'checkout/onepage/success';
            $this->redirect($resultUrl);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
            $this->redirect($resultUrl);
        }
    }

    public function createPaymentTransaction($order, $response)
    {
        $payment = $order->getPayment();
        $payment->setLastTransId($response['MFUKEY']);
        $payment->setTransactionId($response['MFUKEY']);
        $payment->setAdditionalInformation(
            [Transaction::RAW_DETAILS => $response]
        );
        $formatedPrice = $order->getBaseCurrency()->formatTxt(
            $order->getGrandTotal()
        );
        $iFramePaymentAction = $this->scopeConfig->getValue('payment/curbstone_iframe/payment_action', ScopeInterface::SCOPE_STORE);
        if ($iFramePaymentAction == 'authorize') {
            $message = __('The authorized amount is %1.', $formatedPrice);
            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($response['MFUKEY'])
                ->setAdditionalInformation(
                    [Transaction::RAW_DETAILS => $response]
                )->setFailSafe(true)
                ->build(Transaction::TYPE_AUTH);
        } else {
            $message = __('The captured amount is %1.', $formatedPrice);
            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($response['MFUKEY'])
                ->setAdditionalInformation(
                    [Transaction::RAW_DETAILS => $response]
                )->setFailSafe(true)
                ->build(Transaction::TYPE_CAPTURE);
        }
        $payment->addTransactionCommentsToOrder(
            $transaction,
            $message
        );
        $payment->setParentTransactionId(null);
        $payment->save();
        $order->save();
    }


    public function createVaultToken($response)
    {
        try {
            $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
            $paymentToken->setGatewayToken($response['MFUKEY']);
            $paymentToken->setExpiresAt($this->getExpirationDate($response['MFEDAT']));
            $paymentToken->setIsVisible(true);
            $paymentToken->setIsActive(true);
            $paymentToken->setCustomerId($this->customerSession->getCustomer()->getId());
            $paymentToken->setPaymentMethodCode(ConfigProvider::CODE);

            $paymentToken->setTokenDetails($this->convertDetailsToJSON([
                'title' => 'Curbstone',
                'type' => substr($response['MFRVNA'], 0, 2),
                'maskedCC' => "****" . substr($response['MFCARD'], -4),
                'expirationDate' => implode("/", str_split($response['MFEDAT'], 2)),
            ]));

            $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));

            $this->paymentTokenRepository->save($paymentToken);
            $this->messageManager->addSuccessMessage(__('Card has been saved.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }
    }

    protected function generatePublicHash(PaymentTokenInterface $paymentToken)
    {
        $hashKey = $paymentToken->getGatewayToken();
        if ($paymentToken->getCustomerId()) {
            $hashKey = $paymentToken->getCustomerId();
        }

        $hashKey .= $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . $paymentToken->getTokenDetails();

        return $this->encryptor->getHash($hashKey);
    }

    private function convertDetailsToJSON($details)
    {
        $json = \Zend_Json::encode($details);
        return $json ? $json : '{}';
    }

    /**
     * @return string
     */
    private function getExpirationDate($cardExpiry)
    {
        $cardExpiry = str_split($cardExpiry, 2);
        $expDate = new \DateTime(
            2000 + $cardExpiry[1]
            . '-'
            . $cardExpiry[0]
            . '-'
            . '01'
            . ' '
            . '00:00:00',
            new \DateTimeZone('UTC')
        );
        return $expDate->format('Y-m-d 00:00:00');
    }

    /**
     * Retrieve request object
     *
     * @return RequestInterface
     */
    public function getRequest() :RequestInterface
    {
        return $this->request;
    }

    /**
     * Retrieve response object
     *
     * @return ResponseInterface
     */
    public function getResponse() :ResponseInterface
    {
        return $this->response;
    }

    /**
     * Set redirect into response
     *
     * @param string $path
     * @param array $arguments
     * @return ResponseInterface
     */
    protected function redirect($path, $arguments = []) :ResponseInterface
    {
        $this->redirect->redirect($this->getResponse(), $path, $arguments);
        return $this->getResponse();
    }
}
