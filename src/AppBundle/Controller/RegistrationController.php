<?php

namespace AppBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Post;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use AppBundle\Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This controller provides for user registration.
 */
class RegistrationController extends FOSRestController
{
    /**
     * POST: Create a new user.
     * This endpoint should be used for registration.
     * Checks for duplicate username and gt_email, and will throw a error code
     * of 400 with a message if violated.
     *
     * @POST("/rest/user")
     */
    public function createUserAction(Request $request)
    {
        $db = Database::getInstance();

        // Makes sure the three required fields are present
        if ($request->request->has('Username') && $request->request->has('Password') && $request->request->has('GT_Email')) {
            $username = $request->request->get('Username');
            $password = $request->request->get('Password');
            $gt_email = $request->request->get('GT_Email');

            // check for duplicate username or gt_email
            $result = $db->query("SELECT * FROM User WHERE Username='" . $username . "'");
            $data = $result->fetch_all(MYSQLI_ASSOC);
            if ($result->num_rows > 0) {
                $jsr = new JsonResponse(array('error' => 'The username already exists!'));
                $jsr->setStatusCode(400);
                return $jsr;
            }
            $result = $db->query("SELECT * FROM User WHERE GT_Email='" . $gt_email . "'");
            $data = $result->fetch_all(MYSQLI_ASSOC);
            if ($result->num_rows > 0) {
                $jsr = new JsonResponse(array('error' => 'The email already exists!'));
                $jsr->setStatusCode(400);
                return $jsr;
            }

            // If user has additional parameters like Major and Year, then we'll add them too.
            $year = "NULL";
            $major = "NULL";
            if ($request->request->has("Major_Name")) {
                $temp = $request->request->get("Major_Name");
                // make sure the major is valid
                $result = $db->query("SELECT * FROM Major WHERE Major_Name='" . $temp . "'");
                $dataMajors = $result->fetch_all(MYSQLI_ASSOC);
                if ($result->num_rows < 1) {
                    $jsr = new JsonResponse(array('error' => 'Invalid major!'));
                    $jsr->setStatusCode(400);
                    return $jsr;
                }
                $major = "'" . $temp . "'";
            }
            if ($request->request->has("Year")) {
                $temp = $request->request->get("Year");
                // make sure the year is valid
                if (strcmp($temp, 'Freshman') == 0 || strcmp($temp, 'Sophomore') == 0 || strcmp($temp, 'Junior') == 0 || strcmp($temp, 'Senior') == 0) {
                    $year = "'" . $temp . "'";
                } else {
                    $jsr = new JsonResponse(array('error' => 'Invalid year!'));
                    $jsr->setStatusCode(400);
                    return $jsr;
                }
            }

            // actually add the entry
            $sql = "INSERT INTO User (Username, Password, GT_Email, Year, Major_Name, isAdmin)
            VALUES ('" . $username . "', '" . $password . "', '" . $gt_email . "', " . $year . ", " . $major . ", 0)";

            // if success return 201, else return 400
            if ($db->query($sql)) {
                $response = new Response();
                $response->setStatusCode(201);
                return $response;
            } else {
                $jsr = new JsonResponse(array('error' => $db->error));
                $jsr->setStatusCode(400);
                return $jsr;
            }
        } else {
            $jsr = new JsonResponse(array('error' => 'Required fields are missing.'));
            $jsr->setStatusCode(400);
            return $jsr;
        }
    }
}
