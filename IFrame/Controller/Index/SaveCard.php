<?php
/* Copr. 2018 Curbstone Corporation MLP V0.92 */

namespace Curbstone\IFrame\Controller\Index;

use Curbstone\IFrame\Helper\CurbstoneLog;
use Curbstone\IFrame\Model\Payment\CardOnFile;
use Curbstone\IFrame\Model\Ui\ConfigProvider;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\PaymentTokenRepository;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;

class SaveCard implements ActionInterface
{
    /**
     * @var mixed
     */
    protected $cardOnFile;
    /**
     * @var mixed
     */
    protected $payLog;
    /**
     * @var mixed
     */
    protected $paymentTokenFactory;
    /**
     * @var mixed
     */
    protected $encryptor;
    /**
     * @var mixed
     */
    protected $customerSession;

    protected ManagerInterface $messageManager;
    protected RequestInterface $request;
    protected ResultFactory $resultFactory;
    protected RedirectInterface $redirect;
    protected ResponseInterface $response;
    protected PaymentTokenRepository $paymentTokenRepository;

    /**
     * @param ResultFactory $resultFactory
     * @param CurbstoneLog $payLog
     * @param CardOnFile $cardOnFile
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param EncryptorInterface $encryptor
     * @param PaymentTokenRepository $paymentTokenRepository
     * @param Session $customerSession
     * @param ManagerInterface $messageManager
     * @param RequestInterface $request
     * @param RedirectInterface $redirect
     * @param ResponseInterface $response
     */
    public function __construct(
        ResultFactory $resultFactory,
        CurbstoneLog $payLog,
        CardOnFile $cardOnFile,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        EncryptorInterface $encryptor,
        PaymentTokenRepository $paymentTokenRepository,
        Session $customerSession,
        ManagerInterface $messageManager,
        RequestInterface $request,
        RedirectInterface $redirect,
        ResponseInterface $response
    ) {
        $this->resultFactory = $resultFactory;
        $this->cardOnFile = $cardOnFile;
        $this->payLog = $payLog;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->encryptor = $encryptor;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->request = $request;
        $this->redirect = $redirect;
        $this->response = $response;
    }

    public function execute()
    {
        $request = $this->request->getParams();
        $this->createCardOnFile($request);
    }

    protected function redirect($path, $arguments = []): ResponseInterface
    {
        $this->redirect->redirect($this->response, $path, $arguments);
        return $this->response;
    }

    /**
     * @param $response
     */
    protected function createCardOnFile($response)
    {
        $resultUrl = 'vault/cards/listaction';
        $cardOnFileAuthorize = $this->cardOnFile->convertAuthorizeData($response, $this->customerSession->getCustomer());

        $this->payLog->writePaylog("Authorize Card-on-File Request Data:");
        $this->payLog->writePaylog(print_r($cardOnFileAuthorize, true));

        $authorizeResponse = $this->excuteDsiApi($cardOnFileAuthorize);
        if ($authorizeResponse) {
            $this->payLog->writePaylog("Authorize Card-on-File Response Data:");
            $this->payLog->writePaylog(print_r($authorizeResponse, true));
            $cardOnFileVerify = $this->cardOnFile->convertVerifyData($authorizeResponse, $this->customerSession->getCustomer());
            $this->payLog->writePaylog("Verify Card-on-File Request Data:");
            $this->payLog->writePaylog(print_r($cardOnFileVerify, true));
            $verifyResponse = $this->excuteDsiApi($cardOnFileVerify);
            if (array_key_exists('MFRTRN', $verifyResponse)) {
                switch ($verifyResponse['MFRTRN']) {
                    case 'UG':
                        $this->messageManager->addSuccessMessage(
                            __('Transaction Processed:  ' . $response['MFRTXT'].'.' . 'Card has been saved.')
                        );
                        $this->createVaultToken($verifyResponse);
                        $this->redirect($resultUrl);
                        break;
                    case 'UN':
                        $this->messageManager->addSuccessMessage(
                            __('Transaction Processed:  ' . $response['MFRTXT'])
                        );
                        break;
                    case 'UL':
                    default:
                        $this->messageManager->addErrorMessage(
                            __('Field Error Code: ' . $response['MFATAL'] . ' - ' . $response['MFRTXT'])
                        );
                        break;
                }
            } else {
                $this->messageManager->addErrorMessage(__('Sorry, something went wrong. Please try again later.'));
                $this->redirect($resultUrl);
            }
        } else {
            $this->messageManager->addErrorMessage(__('Sorry, something went wrong. Please try again later.'));
            $this->redirect($resultUrl);
        }

    }

    /**
     * @param $response
     */
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
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            //throw $e;
        }
    }

    /**
     * @param PaymentTokenInterface $paymentToken
     * @return mixed
     */
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

    /**
     * @param $details
     * @return mixed
     */
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
     * @param $cardOnFilePayload
     * @return mixed
     */
    public function excuteDsiApi($cardOnFilePayload)
    {
        $payload_string = json_encode($cardOnFilePayload);
        $DSI_API_URL = 'https://c3sbx.net/dsi/';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $DSI_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload_string);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($payload_string), 'Content-Type: application/json', 'Accept: application/json'));
        error_log(print_r($payload_string, true));
        $result = curl_exec($ch);
        if ($result == false) {
            echo '<br><hr><strong>DSI Curl error#: ' . curl_errno($ch) . "<br>";
            echo 'DSI Curl error: ' . curl_error($ch) . "</strong><hr>";
        }
        curl_close($ch);
        $reponseData = json_decode($result, true);
        $this->_payLog->writePaylog("Response Card-on-File Data:");
        $this->_payLog->writePaylog(print_r($reponseData, true));
        if (array_key_exists('MFRTRN', $reponseData)) {
            switch ($reponseData['MFRTRN']) {
                case 'UG':
                    $confirmation_msg = 'Transaction Processed:  ' . $reponseData['MFRTXT'];
                    return $reponseData;
                    break;
                case 'UN':
                    $confirmation_msg = 'Transaction Processed:  ' . $reponseData['MFRTXT'];
                    break;
                case 'UL':
                default:
                    $confirmation_msg = 'Field Error Code: ' . $reponseData['MFATAL'] . ' - ' . $reponseData['MFRTXT'];
                    break;
            }
        } else {
            $confirmation_msg = '';
            $txn_status = '';
        }
        return array();
    }
}
