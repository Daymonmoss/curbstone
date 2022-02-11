<?php
/**
 * Copyright © 2018 CollinsHarper. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace Curbstone\IFrame\Gateway\Config;

use Curbstone\IFrame\Model\Ui\ConfigProvider;
/**
 * Class Config
 * @codeCoverageIgnore
 */
class Config extends \Magento\Payment\Gateway\Config\Config
{
    const KEY_TITLE = "title";

    public function getTitle()
    {
        $this->setMethodCode(ConfigProvider::CODE);
        return $this->getValue(self::KEY_TITLE);
    }
}
