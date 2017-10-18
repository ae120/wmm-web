<?php
// Yml load 组件
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Route;

// 引入路由组件
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

// 引入 Symfony Http Response组件
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Common\HttpRequest;

$context = new RequestContext(
    HttpRequest::getBaseUrl(),
    HttpRequest::getMethod(),
    HttpRequest::getHost(),
    HttpRequest::getScheme(),
    HttpRequest::getPort(),
    443,
    HttpRequest::getPathInfo(),
    HttpRequest::getQueryString()
);

static $matcher = null;

if (is_null($matcher)) {
    $collection = new \Symfony\Component\Routing\RouteCollection();
    $collection->addResource(new FileResource(realpath(__DIR__ . '/src/Config/Routes.php')));
    $routeConfig = (array) new \Config\Routes();
    foreach ($routeConfig as $name => $config) {
        $defaults = isset($config['defaults']) ? $config['defaults'] : array();
        $requirements = isset($config['requirements']) ? $config['requirements'] : array();
        $options = isset($config['options']) ? $config['options'] : array();
        $host = isset($config['host']) ? $config['host'] : '';
        $schemes = isset($config['schemes']) ? $config['schemes'] : array();
        $methods = isset($config['methods']) ? $config['methods'] : array();
        $condition = isset($config['condition']) ? $config['condition'] : null;
        $route = new Route($config['path'], $defaults, $requirements, $options, $host, $schemes, $methods, $condition);
        $collection->add($name, $route);
    }
    $matcher = new UrlMatcher($collection, $context);
}

$uri = HttpRequest::getPathInfo();

try {
    $matches = $matcher->match($uri);
} catch (\Symfony\Component\Routing\Exception\ResourceNotFoundException $ne) {
    $response = new JsonResponse(['code' => 404, 'message' => $ne->getMessage()]);
    $response->send();
    return ;
} catch (\Symfony\Component\Routing\Exception\MethodNotAllowedException $e) {
    $response = new JsonResponse(['code' => 405, 'message' => 'Method now allowed']);
    $response->send();
    return ;
} catch (\Exception $e) {
    $response = new JsonResponse(['code' => 500, 'message' => $e->getMessage()]);
    $response->send();
    return ;
}

try {
    $controllerAndAction = explode(':', $matches['_controller']);
    $controllerClass = "\\" . $controllerAndAction['0'] . "\\Controller\\{$controllerAndAction[1]}Controller";

    if (!class_exists($controllerClass, true)) {
        throw new \Exception(sprintf("Can not found controller: %s", $controllerClass), 404);
    }

    $controllerInstance = new $controllerClass();
    $action = $controllerAndAction['2'] . 'Action';
    $classReflection = new ReflectionClass($controllerInstance);

    $method = $classReflection->getMethod($action);
    $passParams = [];
    foreach ($method->getParameters() as $parameter) {
//        if ($class = $parameter->getClass()) {
//            if ($class->getName() == 'Common\HttpRequest') {
//                $passParams[$parameter->getName()] = HttpRequest;
//            } else {
//                throw new \Exception(sprintf("Un support class %s in action inject", $class), 500);
//            }
//        } else {
        if (!isset($matches[$parameter->getName()]) && !$parameter->isDefaultValueAvailable()) {
            throw new \Exception(sprintf("Param %s need set a value in route", $parameter->getName()), 500);
        }
        if (isset($matches[$parameter->getName()])) {
            $passParams[$parameter->getName()] = $matches[$parameter->getName()];
        }
//        }
    }

    $response = call_user_func_array([$controllerInstance, $action], $passParams);
    if (!$response instanceof Response || !$response instanceof JsonResponse) {
        throw new \Exception(sprintf("The response must be instanceof %s or %s", 'Symfony\Component\HttpFoundation\Response', 'Symfony\Component\HttpFoundation\JsonResponse'));
    }
    $response->send();
} catch (\Exception $e) {
    $code = $e->getCode() ? $e->getCode() : 500;
    $response = new JsonResponse(['code' => $code, 'message' => $e->getMessage()]);
    $response->send();
}
