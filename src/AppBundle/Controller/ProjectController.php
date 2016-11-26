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
 * This controller allows the user to obtain information on
 * projects.
 */
class ProjectController extends FOSRestController implements AuthenticationRequiredController
{
    /**
    * GET: Gets the information associated with the project name.
    * This endpoint should be used for the View and Apply Project screen.
    * The project name should be given as a request parameter.
    *
    * This only gets the basic parameters (no categories nor requirements).
    *
    * @Get("/rest/project/{project_name}")
    */
    public function getProjectInformation(Request $request, $project_name) {
        $db = Database::getInstance();

        $sql = "SELECT * FROM Project
        WHERE Project.Project_Name='" . $project_name . "'";

        if(!$result = $db->query($sql)){
            $jsr = new JsonResponse(array('error' => $db->error));
            $jsr->setStatusCode(400);
            return $jsr;
        }

        $result = $db->query($sql);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        if ($result->num_rows < 1) {
            $jsr = new JsonResponse(array('error' => 'Invalid project specified.', 'sql' => $sql));
            $jsr->setStatusCode(400);
            return $jsr;
        }
        return new JsonResponse($data[0]);
    }

    /**
    * GET: Gets the categories associated with a project.
    *
    * @Get("/rest/project/{project_name}/categories")
    */
    public function getProjectCategories(Request $request, $project_name) {
        $db = Database::getInstance();

        // check to see if project is valid
        $sql = "SELECT * FROM Project
        WHERE Project.Project_Name='" . $project_name . "'";

        if(!$result = $db->query($sql)){
            $jsr = new JsonResponse(array('error' => $db->error));
            $jsr->setStatusCode(400);
            return $jsr;
        }

        $result = $db->query($sql);
        //for ($set = array (); $row = $result->fetch_assoc(); $set[] = $row);
        //return new JsonResponse(array('rows' => $result->num_rows, 'data' => $set));
        $data = $result->fetch_all(MYSQLI_ASSOC);
        if ($result->num_rows < 1) {
            $jsr = new JsonResponse(array('error' => 'Invalid project specified.', 'sql' => $sql));
            $jsr->setStatusCode(400);
            return $jsr;
        }

        // now check for the project's categories
        $sql = "SELECT Category.Category_Name FROM Project
        INNER JOIN Project_Category ON Project.Project_Name = Project_Category.Project_Name
        INNER JOIN Category ON Project_Category.Category_Name = Category.Category_Name
        WHERE Project.Project_Name='" . $project_name . "'";

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
    * GET: Gets the requirements associated with a project.
    *
    * @Get("/rest/project/{project_name}/requirements")
    */
    public function getProjectRequirements(Request $request, $project_name) {
        $db = Database::getInstance();

        // check to see if project is valid
        $sql = "SELECT * FROM Project
        WHERE Project.Project_Name='" . $project_name . "'";

        if(!$result = $db->query($sql)){
            $jsr = new JsonResponse(array('error' => $db->error));
            $jsr->setStatusCode(400);
            return $jsr;
        }

        $result = $db->query($sql);
        //for ($set = array (); $row = $result->fetch_assoc(); $set[] = $row);
        //return new JsonResponse(array('rows' => $result->num_rows, 'data' => $set));
        $data = $result->fetch_all(MYSQLI_ASSOC);
        if ($result->num_rows < 1) {
            $jsr = new JsonResponse(array('error' => 'Invalid project specified.', 'sql' => $sql));
            $jsr->setStatusCode(400);
            return $jsr;
        }

        // now check for the project's requirements
        $sql = "SELECT Requirement.Requirement, Requirement.Requirement_Type FROM Project
        INNER JOIN Requirement ON Project.Project_Name = Requirement.Project_Name
        WHERE Project.Project_Name='" . $project_name . "'";

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
