<?php
/* Copr. 2018 Curbstone Corporation MLP V0.92 */

namespace Curbstone\IFrame\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Store\Model\ScopeInterface;
use Magento\Vault\Model\CustomerTokenManagement;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'curbstone_iframe';
    const VAULT_CODE = 'curbstone_iframe_vault';
    protected $ccConfig;
    private $icons = [];
    protected $assetSource;
    protected $_urlInterface;
    private $config;
    private $customerTokens = array();

    public function __construct(
        ScopeConfigInterface $config,
        CcConfig $ccConfig,
        Source $assetSource,
        UrlInterface $urlInterface,
        CustomerTokenManagement $customerTokenManagement
    ) {
        $this->ccConfig = $ccConfig;
        $this->config = $config;
        $this->assetSource = $assetSource;
        $this->_urlInterface = $urlInterface;
        $this->customerTokenManagement = $customerTokenManagement;
    }

    public function getConfig()
    {
        $methodCode = 'cardonfile';
        return [
            'payment' => [
                self::CODE => [
                    "vaultCode" => self::VAULT_CODE,
                    "isEnableCardOnFile" => $this->config->getValue('payment/curbstone_iframe/store_card', ScopeInterface::SCOPE_STORE),
                    'listTokens' => $this->getDataTokenCards(),
                    'availableTypes' => [$methodCode => $this->config->getValue('payment/curbstone_iframe/cctypes', ScopeInterface::SCOPE_STORE)],
                    'months' => [$methodCode => $this->getCcMonths()],
                    'years' => [$methodCode => $this->getCcYears()],
                    'hasVerification' => [$methodCode => $this->hasVerification($methodCode)],
                    'cvvImageUrl' => [$methodCode => $this->getCvvImageUrl()],
                    'icons' => $this->getIcons(),
                    'saveCardUrl' => $this->_urlInterface->getUrl('curbstone_iframe/index/saveCard')
                ]
            ],
            'vault' => [
                self::CODE => [
                    "is_enabled" => true,
                ]
            ]
        ];
    }

    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    protected function getCcMonths()
    {
        return $this->ccConfig->getCcMonths();
    }

    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    protected function getCcYears()
    {
        return $this->ccConfig->getCcYears();
    }

    protected function hasVerification($methodCode)
    {
        return true;
    }

    protected function getCvvImageUrl()
    {
        return $this->ccConfig->getCvvImageUrl();
    }

    /**
     * Get icons for available payment methods
     *
     * @return array
     */
    public function getIcons()
    {
        if (!empty($this->icons)) {
            return $this->icons;
        }

        $types = $this->ccConfig->getCcAvailableTypes();
        foreach (array_keys($types) as $code) {
            if (!array_key_exists($code, $this->icons)) {
                $asset = $this->ccConfig->createAsset('Magento_Payment::images/cc/' . strtolower($code) . '.png');
                $placeholder = $this->assetSource->findSource($asset);
                if ($placeholder) {
                    list($width, $height) = getimagesize($asset->getSourceFile());
                    $this->icons[$code] = [
                        'url' => $asset->getUrl(),
                        'width' => $width,
                        'height' => $height
                    ];
                }
            }
        }

        return $this->icons;
    }

    private function getCustomerTokens()
    {
        if (empty($this->customerTokens)) {
            $this->customerTokens = $this->customerTokenManagement->getCustomerSessionTokens();
        }
        return $this->customerTokens;
    }

    public function getDataTokenCards()
    {
        $result = array();
        $tokens = $this->getCustomerTokens();
        foreach ($tokens as $token) {
            $tokenDetails = json_decode($token->getDetails(), true);
            if(!empty($tokenDetails)) {
                $tokenRender = array();
                $tokenRender['maskCC'] = $tokenDetails['maskedCC'];
                $tokenRender['title'] = $tokenDetails['title'];
                $tokenRender['expired'] = $tokenDetails['expirationDate'];
                $tokenRender['type'] = $tokenDetails['type'];
                $tokenRender['token'] = $token->getGatewayToken();
                $result[] = $tokenRender;
            }
        }
        return $result;
    }
}
