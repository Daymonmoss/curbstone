<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace Curbstone\CardOnFile\Block\Customer;

use Curbstone\IFrame\Model\Ui\ConfigProvider;
use Magento\Framework\View\Element\Template;

class NewCard extends Template
{
    protected $layoutProcessors;
    protected $configProvider;

    public function __construct(
        ConfigProvider $configProvider,
        Template\ConText $context,
        array $layoutProcessors = [],
        array $data = []

    ) {
        parent::__construct($context, $data);
        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->layoutProcessors = $layoutProcessors;
        $this->configProvider = $configProvider;
    }

    public function getJsLayout()
    {
        foreach ($this->layoutProcessors as $processor) {
            $this->jsLayout = $processor->process($this->jsLayout);
        }
        return \Zend_Json::encode($this->jsLayout);
    }

    public function getCheckoutConfig()
    {
        return $this->configProvider->getConfig();
    }
}
