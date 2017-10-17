<?php
namespace WebServer;

use Workerman\Protocols\Http;

class WebServer extends \Workerman\WebServer
{
    /**
     * 重写处理http请求逻辑.
     *
     * @param \Workerman\Connection\TcpConnection $connection
     *
     * @return mixed
     */
    public function onMessage($connection)
    {
        // REQUEST_URI.
        $urlInfo = parse_url($_SERVER['REQUEST_URI']);
        if (!$urlInfo) {
            Http::header('HTTP/1.1 400 Bad Request');
            $connection->close('<h1>400 Bad Request</h1>');
            return;
        }
        $rootDir = isset($this->serverRoot[$_SERVER['SERVER_NAME']]) ? $this->serverRoot[$_SERVER['SERVER_NAME']] : current($this->serverRoot);
        $index = $rootDir . '/index.php';
        if (!file_exists($index)) {
            Http::header('HTTP/1.1 404 Not Found');
            $connection->close('<h1>400 Bad Request</h1>');
            return;
        }
        $indexFile = realpath($index);
        $workerman_cwd = getcwd();
        chdir($rootDir);
        ini_set('display_errors', 'off');
        ob_start();
        // Try to include php file.
        try {
            // $_SERVER.
            $_SERVER['REMOTE_ADDR'] = $connection->getRemoteIp();
            $_SERVER['REMOTE_PORT'] = $connection->getRemotePort();
            include $indexFile;
        } catch (\Exception $e) {
            // Jump_exit?
            if ($e->getMessage() != 'jump_exit') {
                echo $e;
            }
        }
        $content = ob_get_clean();
        ini_set('display_errors', 'on');
        if (strtolower($_SERVER['HTTP_CONNECTION']) === "keep-alive") {
            $connection->send($content);
        } else {
            $connection->close($content);
        }
        chdir($workerman_cwd);
    }

}
