<?php
namespace  Common;

use Monolog\Logger as MonoLogger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Yaml\Yaml;

class Logger
{
    public static function getLogger()
    {
        $logConfig = Yaml::parse(__DIR__ . '/../config/log.yml');

        $logger = new MonoLogger($logConfig['channel']);
        $logger->pushHandler(new StreamHandler($logConfig['path'], MonoLogger::DEBUG));
        return $logger;
    }

}