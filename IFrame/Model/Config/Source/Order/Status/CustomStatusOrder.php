<?php
/* Copr. 2018 Curbstone Corporation MLP V0.92 */

namespace Curbstone\IFrame\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status;

class CustomStatusOrder extends Status
{
    public function toOptionArray()
    {
        $statuses = $this->_stateStatuses
            ? $this->_orderConfig->getStateStatuses($this->_stateStatuses)
            : $this->_orderConfig->getStatuses();

        $options = [['value' => '', 'label' => __('-- Please Select --')]];
            $options[] = ['value' => 'pending', 'label' => 'Pending'];
            $options[] = ['value' => 'processing', 'label' => 'Processing'];
        return $options;
    }
}
