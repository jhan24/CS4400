<?php

namespace AppBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Get;
use Symfony\Component\HttpFoundation\JsonResponse;
class FruitsController extends FOSRestController
{
    /**
     * GET Route annotation.
     * @Get("/rest/fruits")
     */
    public function getFruitsAction()
    {
        $fruits = array (
            "fruits"  => array("a" => "orange", "b" => "banana", "c" => "apple"),
            "numbers" => array(1, 2, 3, 4, 5, 6),
            "holes"   => array("first", 5 => "second", "third")
        );

        return new JsonResponse(array('fruits' => $fruits));
    }
}
