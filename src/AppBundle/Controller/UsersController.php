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

// TODO: Remove checks for cookie credentials being valid, as this is now taken care of in the AuthenticationListener.
// TODO: Make the get major's department query less stupid.
/**
 * This controller provides for all the necessary actions a user would
 * need to do to edit his own data, as well as some extraneous functions.
 * NOTE: To register a user, use the RegistrationController.
 * This is because user registration is non-authenticated, while editing
 * user profile needs authentication.
 */
class UsersController extends FOSRestController implements AuthenticationRequiredController
{
    /**
     * GET: List of all users. No filtering/searching feature included.
     * This endpoint should not be used in the CS4400 project, but is
     * included for testing purposes.
     * @Get("/rest/users")
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
                $major = $temp;
            }
            if ($request->request->has("Year")) {
                $temp = $request->request->get("Year");
                // make sure the year is valid
                if (strcmp($temp, 'Freshman') == 0 || strcmp($temp, 'Sophomore') == 0 || strcmp($temp, 'Junior') == 0 || strcmp($temp, 'Senior') == 0) {
                    $year = $temp;
                } else {
                    $jsr = new JsonResponse(array('error' => 'Invalid year!'));
                    $jsr->setStatusCode(400);
                    return $jsr;
                }
            }

            // actually add the entry
            $sql = "INSERT INTO User (Username, Password, GT_Email, Year, Major_Name, isAdmin)
            VALUES ('" . $username . "', '" . $password . "', '" . $gt_email . "', '" . $year . "', '" . $major . "', 0)";

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

    /**
     * PUT: Edit an existing user.
     * This endpoint should be used for profile screen update.
     * Allows for updating of ALL student fields!
     * Front-end needs to restrict the rest.
     *
     * Checks to make sure Major and Year values are valid, though this should also be done in the front-end.
     * Also updates cookies if user updates Username or Password.
     *
     * Only include the fields that will be updated.
     * Username and Password for identification are obtained from the cookies.
     *
     * @PUT("/rest/user")
     */
    public function editUserAction(Request $request)
    {
        $db = Database::getInstance();
        // get the username and password of the currently logged in user through the cookies
        $username = $request->attributes->get('username');
        $password = $request->attributes->get('password');
        // make sure the cookie is still valid, and get the rest of the data associated with the user
        $result = $db->query("SELECT * FROM User WHERE Username='" . $username . "' AND Password='" . $password . "'");
        if (!$result) {
            $jsr = new JsonResponse(array('error' => $db->error));
            $jsr->setStatusCode(400);
            return $jsr;
        }
        $data = $result->fetch_all(MYSQLI_ASSOC);
        if ($result->num_rows < 1) {
            $jsr = new JsonResponse(array('error' => 'Invalid Credentials.'));
            $jsr->setStatusCode(403);
            return $jsr;
        }
        // save the current user's information before it is updated
        $newUsername = $data[0]['Username'];
        $newPassword = $data[0]['Password'];
        $newGTEmail = $data[0]['GT_Email'];
        $gt_email = $data[0]['GT_Email']; // we need the old gt_email to be saved so that we know what the current user's gt_email originally was
        $newMajor = $data[0]['Major_Name'];
        $newYear = $data[0]['Year'];

        $newCookie = 0; // if username or password is changed, the user needs a new cookie for authentication to continue working.
        if ($request->request->has('Username')) { // if user specified a new username, check to make sure it is valid, then change it.
            $newCookie = 1;
            $temp = $request->request->get("Username");
            // only if username isn't the same as previous
            if (strcmp($temp, $username) != 0) {
                // make sure new username doesn't already exist
                $result = $db->query("SELECT * FROM User WHERE Username='" . $temp . "'");
                $data = $result->fetch_all(MYSQLI_ASSOC);
                if ($result->num_rows > 0) {
                    $jsr = new JsonResponse(array('error' => 'The username already exists!'));
                    $jsr->setStatusCode(400);
                    return $jsr;
                }
                $newUsername = $temp;
            }
        }
        if ($request->request->has('Password')) { // if user specified a new password, change it to the new one
            $newCookie = 1;
            $newPassword = $request->request->get("Password");
        }
        if ($request->request->has("GT_Email")) { // if user specified new gt_email
            $temp = $request->request->get("GT_Email");
            // check if gt_email isn't the same as previous (this is why we had to save old value of gt_email, for more readability)
            if (strcmp($temp, $gt_email) != 0) {
                // make sure new username doesn't already exist
                $result = $db->query("SELECT * FROM User WHERE GT_Email='" . $temp . "'");
                $data = $result->fetch_all(MYSQLI_ASSOC);
                if ($result->num_rows > 0) {
                    $jsr = new JsonResponse(array('error' => 'The email already exists!'));
                    $jsr->setStatusCode(400);
                    return $jsr;
                }
                $newGTEmail = $temp;
            }
        }
        if ($request->request->has("Major_Name")) { // if user specified a new major
            $temp = $request->request->get("Major_Name");
            // make sure the major is valid
            $result = $db->query("SELECT * FROM Major WHERE Major_Name='" . $temp . "'");
            $dataMajors = $result->fetch_all(MYSQLI_ASSOC);
            if ($result->num_rows < 1) {
                $jsr = new JsonResponse(array('error' => 'Invalid major!'));
                $jsr->setStatusCode(400);
                return $jsr;
            }
            $newMajor = $temp;
        }
        if ($request->request->has("Year")) { // if user specified a new year
            $temp = $request->request->get("Year");
            // make sure the year is valid
            if (strcmp($temp, 'Freshman') == 0 || strcmp($temp, 'Sophomore') == 0 || strcmp($temp, 'Junior') == 0 || strcmp($temp, 'Senior') == 0) {
                $newYear = $temp;
            } else {
                $jsr = new JsonResponse(array('error' => 'Invalid year!'));
                $jsr->setStatusCode(400);
                return $jsr;
            }
        }

        // do the update statement
        $sql = "UPDATE User SET Username='" . $newUsername . "' , Password='" . $newPassword . "' , GT_Email='" . $newGTEmail . "' , Major_Name='" . $newMajor . "' , Year='" . $newYear . "'WHERE Username='" . $username . "'";

        if ($db->query($sql)) {
            $response = new Response();
            $response->setStatusCode(200);
            if ($newCookie == 1) { // if username or password changed, we need a new cookie
                $response->headers->setCookie(new Cookie('cs4400-username', $newUsername));
                $response->headers->setCookie(new Cookie('cs4400-password', $newPassword));
            }
            return $response;
        } else {
            $jsr = new JsonResponse(array('error' => $db->error));
            $jsr->setStatusCode(400);
            return $jsr;
        }

    }

    /**
     * GET: The department associated with the given Major_Name.
     * Major_Name should be included in the url after /major/.
     * This endpoint should be used when displaying the Edit Profile screen.
     *
     * @Get("/rest/major/{major_name}")
     */
    public function getMajorDepartment(Request $request, $major_name) {
        $db = Database::getInstance();

        // TODO: THERE WAS NO REASON TO DO A JOIN HERE! THIS WAS GOING FROM MANY SIDE TO ONE!!!!! but heck it works so i'm not fixing it for now
        $sql = "SELECT DISTINCT(Department.Dept_Name) FROM Major JOIN Department ON Major.Dept_Name = Department.Dept_Name WHERE Major_Name='" . $major_name . "'";
        $result = $db->query($sql);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        if ($result->num_rows < 1) {
            $jsr = new JsonResponse(array('error' => 'Invalid major specified.'));
            $jsr->setStatusCode(400);
            return $jsr;
        }
        $jsr = new JsonResponse($data[0]);
        $jsr->setStatusCode(200);
        return $jsr;
    }

    /**
     * NOTE: Need to DELETE a user? Go through phpmyadmin and delete from there for now.
     * Deleting will become a endpoint in the AdminController once implemented.
     * Only admins can delete users, after all!
     */

    /**
    * POST: A new application that the current user has applied to.
    * Uses the currently logged in user based on the cookies.
    * Does checks to make sure the user matches the requirements.
    * The name of the project being applied to should be included in the post body.
    *
    * This endpoint should be when the user applies to a project.
    *
    * @Post("/rest/application")
    */
    public function createApplication(Request $request) {
        $db = Database::getInstance();

        // get the username and password of the currently logged in user through the cookies
        $username = $request->attributes->get('username');
        $password = $request->attributes->get('password');

        // check to make sure the project name was given
        if ($request->request->has('Project_Name')) {
            $project_name = $request->request->get('Project_Name');

            // run sql query, make sure current user is valid
            $result = $db->query("SELECT * FROM User INNER JOIN Major ON User.Major_Name = Major.Major_Name WHERE Username='" . $username . "' AND Password='" . $password . "'");
            if (!$result) {
                $jsr = new JsonResponse(array('error' => $db->error));
                $jsr->setStatusCode(400);
                return $jsr;
            }
            $data = $result->fetch_all(MYSQLI_ASSOC);
            if ($result->num_rows < 1) {
                $jsr = new JsonResponse(array('error' => 'The user has either not set a major, or the user is invalid.'));
                $jsr->setStatusCode(400);
                return $jsr;
            }

            // run sql query, make sure project being applied to is valid
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

            // now check the requirements that the project has with the user
            $sql = "SELECT Requirement.Requirement, Requirement.Requirement_Type FROM Project
            INNER JOIN Requirement ON Project.Project_Name = Requirement.Project_Name
            WHERE Project.Project_Name='" . $project_name . "'";

            if(!$result = $db->query($sql)){
                $jsr = new JsonResponse(array('error' => $db->error));
                $jsr->setStatusCode(400);
                return $jsr;
            }

            $result = $db->query($sql);
            $requirements = $result->fetch_all(MYSQLI_ASSOC);

            // end of SQL checking for information, time for actual logic
            // make sure user meets the requirements
            $requirements_length = count($requirements);
            $requirements_met = 0;
            if ($requirements_length > 0) {
                // run sql query to see if the user meets the requirements
                // TODO: Verify this works.
                $sql = "SELECT * FROM User, Major, Requirement
                WHERE Requirement.Project_Name='" . $project_name . "'
                AND User.Username='" . $username . "' AND User.Major_Name=Major.Major_Name
                AND (Requirement.Requirement=User.Year OR Requirement.Requirement=User.Major_Name OR Requirement.Requirement=Major.Dept_Name)";

                $result = $db->query($sql);
                $data = $result->fetch_all(MYSQLI_ASSOC);
                if ($result->num_rows > 0) { // at least one of the requirements were met, so the user is good to go
                    $requirements_met = 1;
                }
            } else {
                $requirements_met = 1;
            }

            // requirements have been met
            if ($requirements_met == 1) {
                // check if the user has already applied to the project in the past
                $sql = "SELECT * FROM Application WHERE Username='" . $username . "' AND Project_Name='" . $project_name . "'";
                $result = $db->query($sql);
                $data = $result->fetch_all(MYSQLI_ASSOC);
                if ($result->num_rows > 0) {
                    $jsr = new JsonResponse(array('error' => 'User has already applied to this project before.', 'sql' => $sql));
                    $jsr->setStatusCode(400);
                    return $jsr;
                }

                // actually add the new application now
                $status = 'pending';
                $date = date("Y-m-d");
                $sql = "INSERT INTO Application (Username, Project_Name, Date, Status)
                VALUES ('" . $username . "', '" . $project_name . "', '" . $date . "', '" . $status . "')";

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
            } else { // otherwise, throw error
                $jsr = new JsonResponse(array('error' => "User does not satisfy requirements of project."));
                $jsr->setStatusCode(401);
                return $jsr;
            }

        } else {
            $jsr = new JsonResponse(array('error' => 'No project was specified.'));
            $jsr->setStatusCode(400);
            return $jsr;
        }

    }

    /**
    * GET: Gets all applications that the currently logged in user has created.
    * Uses the currently logged in user based on the cookies.
    *
    * This endpoint should be when the user views the "My Application" screen.
    *
    * @Get("/rest/application")
    */
    public function getApplications(Request $request) {
        $db = Database::getInstance();

        // get the username and password of the currently logged in user through the cookies
        $username = $request->attributes->get('username');
        $password = $request->attributes->get('password');

        $sql = "SELECT Date, Project_Name, Status FROM Application WHERE Application.Username = '" . $username . "'";

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
