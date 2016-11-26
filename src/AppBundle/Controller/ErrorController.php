<?php

namespace AppBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use AppBundle\Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Delete;

/**
 * This controller provides for error handling in the program.
 * Mostly just deals with authentication issues.
 */
class ErrorController extends FOSRestController
{
    public function invalidCredentials() {
        $jsr = new JsonResponse(array('error' => 'Not authenticated.'));
        $jsr->setStatusCode(403);
        return $jsr;
    }

    public function notAdministrator() {
        $jsr = new JsonResponse(array('error' => 'Not authorized.'));
        $jsr->setStatusCode(401);
        return $jsr;
    }

    public function notStudent() {
        $jsr = new JsonResponse(array('error' => 'Not allowed.'));
        $jsr->setStatusCode(401);
        return $jsr;
    }
}
