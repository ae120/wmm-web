<?php
namespace Api\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class IndexController
{

    public function indexAction(Request $request, $id = 1)
    {

        return new JsonResponse(1);
    }

    public function articlesAction()
    {
        return new JsonResponse();
    }

}
