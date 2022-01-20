<?php

namespace Curbstone\IFrame\Gateway\Validator;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AvailabilityValidator extends AbstractValidator
{
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var StoreInterface
     */
    private $store;


    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($resultFactory);
        $this->store = $storeManager->getStore();
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $isValid = true;
        $merchant_code = $this->scopeConfig->getValue('payment/curbstone_iframe/merchant_code', ScopeInterface::SCOPE_STORES, $this->store);
        if (empty($merchant_code)) {
            return $this->createResult(false, [__('Curbstone Merchant Code are required')]);
        }

        return $this->createResult($isValid);
    }
}
