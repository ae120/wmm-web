<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Workerman\Worker;
use Workerman\Lib\Timer;

$monitorDirs = [realpath(__DIR__.'/../src'), realpath(__DIR__.'/../config')];
// worker
$worker = new Worker();
$worker->name = 'FileMonitor';
$worker->reloadable = false;
$last_mtime = time();

$worker->onWorkerStart = function() {
    global $monitorDirs;
    // watch files only in daemon mode
    if (!Worker::$daemonize) {
        // check mtime of files per second
        Timer::add(1, 'check_files_change', array($monitorDirs));
    }
};
// check files func
function check_files_change($monitorDirs)
{
    global $last_mtime;
    // recursive traversal directory
    $iterators = [];
    foreach ($monitorDirs as $monitorDir) {
        $dirIterator = new RecursiveDirectoryIterator($monitorDir);
        $iterator = new RecursiveIteratorIterator($dirIterator);
        $iterators[] = $iterator;
    }

    foreach ($iterators as $iterator) {
        foreach ($iterator as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            // only check php and yml files
            if (!in_array($extension, ['php', 'yml'])) {
                continue;
            }
            // check mtime
            if ($last_mtime < $file->getMTime()) {
                echo $file." update and reload\n";
                // send SIGUSR1 signal to master process for reload
                posix_kill(posix_getppid(), SIGUSR1);
                $last_mtime = $file->getMTime();
                break;
            }
        }
    }

}