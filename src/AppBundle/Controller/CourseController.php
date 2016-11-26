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
 * courses.
 */
class CourseController extends FOSRestController implements AuthenticationRequiredController
{
    /**
    * GET: Gets the information associated with the Course name.
    * This endpoint should be used for the View Course screen.
    * The Course Number should be given as a request parameter.
    *
    * This only gets the basic parameters (no categories nor requirements).
    *
    * @Get("/rest/course/{course_number}")
    */
    public function getCourseInformation(Request $request, $course_number) {
        $db = Database::getInstance();

        $sql = "SELECT * FROM Course
        WHERE Course.Course_Number='" . $course_number . "'";

        if(!$result = $db->query($sql)){
            $jsr = new JsonResponse(array('error' => $db->error));
            $jsr->setStatusCode(400);
            return $jsr;
        }

        $result = $db->query($sql);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        if ($result->num_rows < 1) {
            $jsr = new JsonResponse(array('error' => 'Invalid Course specified.', 'sql' => $sql));
            $jsr->setStatusCode(400);
            return $jsr;
        }
        return new JsonResponse($data[0]);
    }

    /**
    * GET: Gets the categories associated with a Course.
    *
    * @Get("/rest/course/{course_number}/categories")
    */
    public function getCourseCategories(Request $request, $course_number) {
        $db = Database::getInstance();

        // check to see if Course is valid
        $sql = "SELECT * FROM Course
        WHERE Course.Course_Number='" . $course_number . "'";

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
            $jsr = new JsonResponse(array('error' => 'Invalid Course specified.', 'sql' => $sql));
            $jsr->setStatusCode(400);
            return $jsr;
        }

        // now check for the Course's categories
        $sql = "SELECT Category.Category_Name FROM Course
        INNER JOIN Course_Category ON Course.Course_Number = Course_Category.Course_Number
        INNER JOIN Category ON Course_Category.Category_Name = Category.Category_Name
        WHERE Course.Course_Number='" . $course_number . "'";

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
