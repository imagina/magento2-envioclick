<?php
namespace Imagina\Envioclick\Logger\Handler;

use Monolog\Logger;

class Exception extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/imagina/envioclick/exception.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::CRITICAL;
}
