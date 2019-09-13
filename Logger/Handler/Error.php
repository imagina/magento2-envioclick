<?php
namespace Imagina\Envioclick\Logger\Handler;

use Monolog\Logger;

class Error extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/imagina/envioclick/error.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::ERROR;
}
