<?php
/* Copr. 2018 Curbstone Corporation MLP V0.92 */

namespace Curbstone\IFrame\Helper;

use Magento\Checkout\Helper\Data;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\Quote;
use Magento\Customer\Model\Group;

class RequestDataBuilder extends AbstractHelper
{
    public function __construct(
        Context $context,
        Session $customerSession,
        Session $checkoutSession,
        Data $data
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->checkoutHelper  = $data;
    }

    public function getCheckoutMethod(Quote $quote)
    {
        if ($this->customerSession->isLoggedIn()) {
            return Onepage::METHOD_CUSTOMER;
        }
        if (!$quote->getCheckoutMethod()) {
            if ($this->checkoutHelper->isAllowedGuestCheckout($quote)) {
                $quote->setCheckoutMethod(Onepage::METHOD_GUEST);
            } else {
                $quote->setCheckoutMethod(Onepage::METHOD_REGISTER);
            }
        }

        return $quote->getCheckoutMethod();
    }

    public function prepareGuestQuote(Quote $quote)
    {
        $quote->setCustomerId(null);
        $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
        $quote->setCustomerIsGuest(true);
        $quote->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
    }
}
