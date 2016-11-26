<?php

namespace AppBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Get;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use AppBundle\Database;

/**
 * This controller provides for all the necessary values to populate
 * the dropdowns in the application.
 *
 * Year is also included, even thogh it is not part of the database.
 */
class DropdownController extends FOSRestController implements AuthenticationRequiredController
{

    /**
    * GET: List of all valid majors and their attributes.
    * @Get("/rest/majors")
    */
    public function getMajors() {
        $db = Database::getInstance();
        $sql = "SELECT * FROM Major";
        if(!$result = $db->query($sql)){
            $jsr = new JsonResponse(array('error' => $db->error));
            $jsr->setStatusCode(400);
            return $jsr;
        }

        $result = $db->query($sql);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        return new JsonResponse(array('num_rows' => $result->num_rows, 'data' => $data));
    }

    /**
    * GET: List of valid years.
    * @Get("/rest/years")
    */
    public function getYears() {
        $db = Database::getInstance();
        $sql = "SELECT * FROM Major";
        if(!$result = $db->query($sql)){
            $jsr = new JsonResponse(array('error' => $db->error));
            $jsr->setStatusCode(400);
            return $jsr;
        }

        $result = $db->query($sql);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        return new JsonResponse(array('num_rows' => $result->num_rows, 'data' => $data));
    }

    /**
    * GET: List of all valid categories.
    * @Get("/rest/categories")
    */
    public function getCategories() {
        $db = Database::getInstance();
        $sql = "SELECT * FROM Category";
        if(!$result = $db->query($sql)){
            $jsr = new JsonResponse(array('error' => $db->error));
            $jsr->setStatusCode(400);
            return $jsr;
        }

        $result = $db->query($sql);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        return new JsonResponse(array('num_rows' => $result->num_rows, 'data' => $data));
    }

    /**
    * GET: List of all valid designations.
    * @Get("/rest/designations")
    */
    public function getDesignations() {
        $db = Database::getInstance();
        $sql = "SELECT * FROM Designation";
        if(!$result = $db->query($sql)){
            $jsr = new JsonResponse(array('error' => $db->error));
            $jsr->setStatusCode(400);
            return $jsr;
        }

        $result = $db->query($sql);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        return new JsonResponse(array('num_rows' => $result->num_rows, 'data' => $data));
    }
}
