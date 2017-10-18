<?php
namespace Api\Controller;

use Common\HttpRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class IndexController
{

    public function indexAction($id = 1)
    {
        return new JsonResponse(['method' => HttpRequest::getMethod()]);
    }

    public function articlesAction()
    {
        return new JsonResponse();
    }

}
