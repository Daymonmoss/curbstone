<?php

namespace Curbstone\IFrame\Plugin;

use Magento\Payment\Model\InfoInterface;

class MagentoPaymentMethodAdapterInfoInstance
{
   private InfoInterface $infoInstance;

   public function __construct(InfoInterface $infoInstance) {
       $this->infoInstance = $infoInstance;
   }

    public function afterGetInfoInstance() : InfoInterface
    {
        return $this->infoInstance;
    }
}
