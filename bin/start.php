<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Workerman\Worker;
use WebServer\WebServer;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

try {
    $serverConfig = Yaml::parse(__DIR__ . '/../config/server.yml');
} catch (ParseException $ex) {
    printf("Unable to parse the Server YAML string: %s", $ex->getMessage());
    exit;
}
$host = isset($serverConfig['host']) ? $serverConfig['host'] : '127.0.0.1';
$port = isset($serverConfig['port']) ? $serverConfig['port'] : '80';
$workerCount = isset($serverConfig['worker']) ? $serverConfig['worker'] : 4;
$hostnames = isset($serverConfig['hostnames']) ? $serverConfig['hostnames'] : [];

$web = new WebServer("http://{$host}:{$port}");
//$web->reusePort = true;
$web->count = $workerCount;
foreach ($hostnames as $hostname) {
    $web->addRoot($hostname['name'], $hostname['root']);
}

if ($serverConfig['env'] == 'dev') {
    include __DIR__ . '/FileMonitor.php';
}

Worker::runAll();
