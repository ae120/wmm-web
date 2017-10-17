<?php
use Symfony\Component\Config\FileLocator;

// 引入路由组件
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Generator\UrlGenerator;

// 引入HTTP Request组件
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

$locator = new FileLocator(array(__DIR__ . "/config"));
$loader = new YamlFileLoader($locator);
$collection = $loader->load('routes.yml');

$request = Request::createFromGlobals();
$context = new RequestContext(
    $request->getBaseUrl(),
    $request->getMethod(),
    $request->getHost(),
    $request->getScheme(),
    $request->getPort(),
    443,
    $request->getPathInfo()
);
$matcher = new UrlMatcher($collection, $context);
$urlGenerator = new UrlGenerator($collection, $context);

$uri = $request->server->get('REQUEST_URI');
$position = strpos($uri, '?');
if ($position!== false) {
    $uri = substr($uri, 0, $position);
}

try {
    $matches = $matcher->match($uri);
} catch (\Symfony\Component\Routing\Exception\ResourceNotFoundException $ne) {
    $response = new JsonResponse(['code' => 404, 'message' => $ne->getMessage()]);
    $response->send();
} catch (\Symfony\Component\Routing\Exception\MethodNotAllowedException $e) {
    $response = new JsonResponse(['code' => 405, 'message' => 'Method now allowed']);
    $response->send();
} catch (\Exception $e) {
    $response = new JsonResponse(['code' => 500, 'message' => $e->getMessage()]);
    $response->send();
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
        if ($class = $parameter->getClass()) {
            if ($class->getName() == 'Symfony\Component\HttpFoundation\Request') {
                $passParams[$parameter->getName()] = $request;
            } else {
                throw new \Exception(sprintf("Un support class %s in action inject", $class), 500);
            }
        } else {
            if (!isset($matches[$parameter->getName()]) && !$parameter->isDefaultValueAvailable()) {
                throw new \Exception(sprintf("Param %s need set a value in route", $parameter->getName()));
            }
            if (isset($matches[$parameter->getName()])) {
                $passParams[$parameter->getName()] = $matches[$parameter->getName()];
            }
        }
    }

    $response = call_user_func_array([$controllerInstance, $action], $passParams);
    if (!$response instanceof Response || !$response instanceof JsonResponse) {
        throw new \Exception(sprintf("The response must be instanceof %s or %s", 'Symfony\Component\HttpFoundation\Response', 'Symfony\Component\HttpFoundation\JsonResponse'));
    }
    $response->send();
} catch (\Exception $e) {
    $response = new JsonResponse(['code' => $e->getCode(), 'message' => $e->getMessage()]);
}
