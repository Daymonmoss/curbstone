<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace Curbstone\CardOnFile\Block\Customer;

use Curbstone\IFrame\Gateway\Config\Config;
use Curbstone\IFrame\Model\Ui\ConfigProvider;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Model\CcConfigProvider;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;

class CardRenderer extends AbstractCardRenderer
{
    /**
     * @var Config
     */
    private Config $gatewayConfig;

    public function __construct(
        Template\Context $context,
        CcConfigProvider $iconsProvider,
        Config $gatewayConfig,
        array $data = []
    ){
        parent::__construct($context, $iconsProvider, $data);
        $this->gatewayConfig = $gatewayConfig;
    }

    /**
     * Can render specified token
     *
     * @param PaymentTokenInterface $token
     * @return boolean
     */
    public function canRender(PaymentTokenInterface $token)
    {
        return $token->getPaymentMethodCode() === ConfigProvider::CODE;
    }

    /**
     * @return string
     */
    public function getNumberLast4Digits()
    {
        return $this->getTokenDetails()['maskedCC'];
    }

    /**
     * @return string
     */
    public function getExpDate()
    {
        return $this->getTokenDetails()['expirationDate'];
    }

    /**
     * @return string
     */
    public function getIconUrl()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['url'];
    }

    /**
     * @return int
     */
    public function getIconHeight()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['height'];
    }

    /**
     * @return int
     */
    public function getIconWidth()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['width'];
    }

    public function getPaymentMethodName()
    {
        return $this->gatewayConfig->getTitle();
    }
}
