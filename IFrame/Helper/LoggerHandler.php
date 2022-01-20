<?php

namespace Curbstone\IFrame\Helper;

use Magento\Framework\Logger\Handler\Base as BaseHandler;
use Monolog\Logger;

class LoggerHandler extends BaseHandler
{
    protected $loggerType = Logger::DEBUG;
    protected $fileName = '/var/log/curbstone_iframe.log';
}
