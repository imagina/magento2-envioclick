<?php

namespace Imagina\Envioclick\Logger\Handler;

use Monolog\Logger;

class Info extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/imagina/envioclick/info.log';
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;
}