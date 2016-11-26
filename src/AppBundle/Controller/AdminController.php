<?php

namespace AppBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use AppBundle\Database;

/**
 * This controller provides for all necessary administrator functions.
 */
class AdminController extends FOSRestController implements AdminRequiredController
{
    /**
     * GET: List of all users. Admin testing function.
     * @Get("/rest/admin/users")
     */
    public function getUsersAction(Request $request)
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM User";

        if(!$result = $db->query($sql)){
            $jsr = new JsonResponse(array('error' => $db->error));
            $jsr->setStatusCode(400);
            return $jsr;
        }

        $result = $db->query($sql);
        //for ($set = array (); $row = $result->fetch_assoc(); $set[] = $row);
        //return new JsonResponse(array('rows' => $result->num_rows, 'data' => $set));
        $data = $result->fetch_all(MYSQLI_ASSOC);
        return new JsonResponse(array('num_rows' => $result->num_rows, 'data' => $data));
    }
}
