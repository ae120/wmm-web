<?php
namespace Common;

class HttpRequest
{

    public static function getRequestUri()
    {
        return $_SERVER['REQUEST_URI'];
    }

    public static function getPathInfo()
    {
        if (null === ($requestUri = self::getRequestUri())) {
            return '/';
        }
        $pos = strpos($requestUri, '?');
        if (false !== $pos) {
            return substr($requestUri, 0, $pos);
        } else {
            return $requestUri;
        }
    }

    public static function getBaseUrl()
    {
        return $_SERVER['SCRIPT_FILENAME'];
    }

    public static function getMethod()
    {
        return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    }

    public static function getHost()
    {
        if (isset($_SERVER['SERVER_NAME'])) {
            return $_SERVER['SERVER_NAME'];
        }
        if (isset($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        }
        return '';
    }

    public static function getScheme()
    {
        return !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    }

    public static function getPort()
    {
        return $_SERVER['HTTP_HOST'];
    }

    public static function getQueryString()
    {
        return !empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    }

    public static function getQuery($key, $default = '')
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    public static function getQuerys()
    {
        return $_GET;
    }

    public static function getPost($key, $default = '')
    {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }

    public static function getPosts()
    {
        return $_POST;
    }

}
