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


// TODO: IMPORTANT: View Application Report screen endpoint is missing.
// I have no clue how to do the SQL tbh.

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

    /**
     * GET: List of all applications for the admin view.
     * This endpoint should be used in the View Applications screen.
     *
     * Although username should not be included in the front-end display view,
     * it is still included because in order to update an application, the username
     * must be provided in the post body.
     *
     * @Get("/rest/admin/applications")
     */
    public function getApplicationsAction(Request $request)
    {
        $db = Database::getInstance();
        $sql = "SELECT Application.Project_Name, User.Major_Name, User.Year, Application.Status, User.Username FROM Application
        INNER JOIN User on Application.Username = User.Username";

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
     * PUT: Updates the status of an application.
     * The username and project name must be provided in the post body as
     * Username and Project_Name respectively.
     *
     * This endpoint should be used on the view applications page, when the admin
     * changes the status of an application by pressing accept or reject.
     *
     * @PUT("/rest/admin/application")
     */
    public function updateApplicationStatus(Request $request)
    {
        $db = Database::getInstance();
        // check that Username and Project_Name exist in the post body, if not throw an error
        if ($request->request->has('Username') && $request->request->has('Project_Name') && $request->request->has('Status')) {
            $username = $request->request->get('Username');
            $project_name = $request->request->get('Project_Name');
            $status = $request->request->get('Status');

            // make sure the status given is valid
            if (strcmp($status, "pending") != 0 && strcmp($status, "accepted") != 0 && strcmp($status, "rejected") != 0) {
                $jsr = new JsonResponse(array('error' => 'Invalid status given.'));
                $jsr->setStatusCode(400);
                return $jsr;
            }

            // do a check to make sure the application exists
            $sql = "SELECT Application.Project_Name, User.Major_Name, User.Year, Application.Status FROM Application
            INNER JOIN User on Application.Username = User.Username";

            // check for duplicate username or gt_email
            $result = $db->query("SELECT * FROM Application WHERE Username='" . $username . "' AND Project_Name='" . $project_name . "'");
            $data = $result->fetch_all(MYSQLI_ASSOC);
            if ($result->num_rows < 1) {
                $jsr = new JsonResponse(array('error' => 'The application does not exist.'));
                $jsr->setStatusCode(400);
                return $jsr;
            }

            // if it exists, lets update it to the given status

            $sql = "UPDATE Application SET Status='" . $status . "' WHERE Username='" . $username . "' AND Project_Name='" . $project_name . "'";
            // if it works, give an empty 200 success response, else throw an error with the db err message.
            if ($db->query($sql)) {
                $response = new Response();
                $response->setStatusCode(200);
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
     * GET: List of the top ten most popular projects (ten projects with the most applications)
     * This endpoint should be used in the Popular Project screen.
     *
     * @Get("/rest/admin/applications/popular")
     */
    public function getPopularApplicationsAction(Request $request)
    {
        $db = Database::getInstance();
        $sql = "SELECT Project_Name, COUNT(Project_Name) AS 'Applications' FROM Application GROUP BY Project_Name ORDER BY COUNT(Project_Name) DESC LIMIT 10;";

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
     * POST: Creates a new project in the database.
     * This endpoint should be used in the Add a Project screen.
     * Include the variables in the post body.
     * Requirements should be given as "Major_Requirement", "Year_Requirement", or "Department_Requirement".
     * Categories should be separated with a comma.
     *
     * @POST("/rest/admin/project")
     */
    public function createProject(Request $request)
    {
        $db = Database::getInstance();

        // Makes sure the three required fields are present
        if ($request->request->has('Project_Name') && $request->request->has('Advisor_Name') && $request->request->has('Advisor_Email') && $request->request->has('Estimated_Students') && $request->request->has('Description') && $request->request->has('Designation_Name') && $request->request->has('Category_Name')) {
            // get basic values

            // get project_name (primary key unique constraint)
            $project_name = $request->request->get('Project_Name');
            // make sure duplicate does not already exist
            $sql = "SELECT * FROM Project WHERE Project_Name = '" . $project_name . "'";
            $result = $db->query($sql);
            $data = $result->fetch_all(MYSQLI_ASSOC);
            if ($result->num_rows > 0) {
                $jsr = new JsonResponse(array('error' => 'Invalid project name!'));
                $jsr->setStatusCode(400);
                return $jsr;
            }

            $advisor_name = $request->request->get('Advisor_Name');
            $advisor_email = $request->request->get('Advisor_Email');
            $estimated_students = $request->request->get('Estimated_Students');
            if (!is_numeric($estimated_students) || $estimated_students <= 0) {
                $jsr = new JsonResponse(array('error' => 'Invalid estimated students!'));
                $jsr->setStatusCode(400);
                return $jsr;
            }
            $description = $request->request->get('Description');

            // get designation (foreign-key constraint)
            $designation = $request->request->get('Designation_Name');
            // make sure the designation is valid
            $sql = "SELECT * FROM Designation WHERE Designation_Name = '" . $designation . "'";
            $result = $db->query($sql);
            $data = $result->fetch_all(MYSQLI_ASSOC);
            if ($result->num_rows < 1) {
                $jsr = new JsonResponse(array('error' => 'Invalid designation!'));
                $jsr->setStatusCode(400);
                return $jsr;
            }

            // get categories (foreign-key constraint)
            $temp = $request->request->get('Category_Name');
            // split the categories up
            $categories = explode(",", $temp);
            $length = count($categories);
            $i = 0;
            // while loop to trim whitespace on the categories and validate them
            while ($i < $length) {
                $categories[$i] = trim($categories[$i]); // trim whitespace
                // check sql to make sure the category is valid
                $sql = "SELECT * FROM Category WHERE Category_Name = '" . $categories[$i] . "'";
                $result = $db->query($sql);
                $data = $result->fetch_all(MYSQLI_ASSOC);
                if ($result->num_rows < 1) {
                    $jsr = new JsonResponse(array('error' => 'Invalid category!'));
                    $jsr->setStatusCode(400);
                    return $jsr;
                }
                $i = $i + 1;
            }
            $categories = array_unique($categories); // remove duplicates if there hapens to be any

            // now, look for the three types of requirements and validate them
            $requirements = array(); // empty array storing requirement values
            $requirement_types = array(); // empty array storing requirement types
            if ($request->request->has('Year_Requirement')) {
                // make sure its valid
                $year_requirement = $request->request->get('Year_Requirement');
                $sql = "SELECT * FROM Year WHERE Year = '" . $year_requirement . "'";
                $result = $db->query($sql);
                $data = $result->fetch_all(MYSQLI_ASSOC);
                if ($result->num_rows < 1) {
                    $jsr = new JsonResponse(array('error' => 'Invalid year!'));
                    $jsr->setStatusCode(400);
                    return $jsr;
                }
                array_push($requirements, $request->request->get('Year_Requirement'));
                array_push($requirement_types, 'Year');
            }
            if ($request->request->has('Major_Requirement')) {
                // make sure its valid
                $major_requirement = $request->request->get('Major_Requirement');
                $sql = "SELECT * FROM Major WHERE Major_Name = '" . $major_requirement . "'";
                $result = $db->query($sql);
                $data = $result->fetch_all(MYSQLI_ASSOC);
                if ($result->num_rows < 1) {
                    $jsr = new JsonResponse(array('error' => 'Invalid major!'));
                    $jsr->setStatusCode(400);
                    return $jsr;
                }
                array_push($requirements, $request->request->get('Major_Requirement'));
                array_push($requirement_types, 'Major');
            }
            if ($request->request->has('Department_Requirement')) {
                // make sure its valid
                $department_requirement = $request->request->get('Department_Requirement');
                $sql = "SELECT * FROM Department WHERE Dept_Name = '" . $department_requirement . "'";
                $result = $db->query($sql);
                $data = $result->fetch_all(MYSQLI_ASSOC);
                if ($result->num_rows < 1) {
                    $jsr = new JsonResponse(array('error' => 'Invalid department!'));
                    $jsr->setStatusCode(400);
                    return $jsr;
                }
                array_push($requirements, $request->request->get('Department_Requirement'));
                array_push($requirement_types, 'Department');
            }

            // since everything has been verified, let's stick the project in first
            $sql = "INSERT INTO Project (Project_Name, Advisor_Name, Advisor_Email, Estimated_Students, Description, Designation_Name)
            VALUES ('" . $project_name . "', '" . $advisor_name . "', '" . $advisor_email . "', '" . $estimated_students . "', '" . $description . "', '" . $designation . "')";

            // if success, we need to add the categories and requirements
            if ($db->query($sql)) {
                // loop through all categories and add them to the join table one by one
                $length = count($categories);
                $c = 0;
                while ($c < $length) {
                    $sql = "INSERT INTO Project_Category (Project_Name, Category_Name)
                    VALUES ('" . $project_name . "', '" . $categories[$c] . "')";

                    if ($db->query($sql)) {
                        // on success do nothing
                    } else {
                        $jsr = new JsonResponse(array('error' => $db->error));
                        $jsr->setStatusCode(400);
                        return $jsr;
                    }
                    $c = $c + 1;
                }

                // now check the requirements
                $length = count($requirements);
                $c = 0;
                while ($c < $length) {
                    $sql = "INSERT INTO Requirement (Requirement, Project_Name, Requirement_Type)
                    VALUES ('" . $requirements[$c] . "', '" . $project_name . "', '" . $requirement_types[$c] . "')";

                    if ($db->query($sql)) {
                        // on success do nothing
                    } else {
                        $jsr = new JsonResponse(array('error' => $db->error));
                        $jsr->setStatusCode(400);
                        return $jsr;
                    }
                    $c = $c + 1;
                }

                // if it reaches this point, we're done! return empty 201.
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
     * POST: Creates a new course in the database.
     * This endpoint should be used in the Add a Course screen.
     * Include the variables in the post body.
     * Categories should be separated with a comma.
     *
     * @POST("/rest/admin/course")
     */
    public function createCourse(Request $request)
    {
        $db = Database::getInstance();

        // Makes sure the three required fields are present
        if ($request->request->has('Course_Name') && $request->request->has('Instructor') && $request->request->has('Course_Number') && $request->request->has('Estimated_Students') && $request->request->has('Designation_Name') && $request->request->has('Category_Name')) {
            // get basic values

            // get course name (primary key unique constraint)
            $course_name = $request->request->get('Course_Name');
            // make sure duplicate does not already exist
            $sql = "SELECT * FROM Course WHERE Course_Name = '" . $course_name . "'";
            $result = $db->query($sql);
            $data = $result->fetch_all(MYSQLI_ASSOC);
            if ($result->num_rows > 0) {
                $jsr = new JsonResponse(array('error' => 'Invalid course name!'));
                $jsr->setStatusCode(400);
                return $jsr;
            }

            // get course number (primary key unique constraint)
            $course_number = $request->request->get('Course_Number');
            // make sure duplicate does not already exist
            $sql = "SELECT * FROM Course WHERE Course_Number = '" . $course_number . "'";
            $result = $db->query($sql);
            $data = $result->fetch_all(MYSQLI_ASSOC);
            if ($result->num_rows > 0) {
                $jsr = new JsonResponse(array('error' => 'Invalid course number!'));
                $jsr->setStatusCode(400);
                return $jsr;
            }

            $instructor = $request->request->get('Instructor');
            $estimated_students = $request->request->get('Estimated_Students');
            if (!is_numeric($estimated_students) || $estimated_students <= 0) {
                $jsr = new JsonResponse(array('error' => 'Invalid estimated students!'));
                $jsr->setStatusCode(400);
                return $jsr;
            }

            // get designation (foreign-key constraint)
            $designation = $request->request->get('Designation_Name');
            // make sure the designation is valid
            $sql = "SELECT * FROM Designation WHERE Designation_Name = '" . $designation . "'";
            $result = $db->query($sql);
            $data = $result->fetch_all(MYSQLI_ASSOC);
            if ($result->num_rows < 1) {
                $jsr = new JsonResponse(array('error' => 'Invalid designation!'));
                $jsr->setStatusCode(400);
                return $jsr;
            }

            // get categories (foreign-key constraint)
            $temp = $request->request->get('Category_Name');
            // split the categories up
            $categories = explode(",", $temp);
            $length = count($categories);
            $i = 0;
            // while loop to trim whitespace on the categories and validate them
            while ($i < $length) {
                $categories[$i] = trim($categories[$i]); // trim whitespace
                // check sql to make sure the category is valid
                $sql = "SELECT * FROM Category WHERE Category_Name = '" . $categories[$i] . "'";
                $result = $db->query($sql);
                $data = $result->fetch_all(MYSQLI_ASSOC);
                if ($result->num_rows < 1) {
                    $jsr = new JsonResponse(array('error' => 'Invalid category!'));
                    $jsr->setStatusCode(400);
                    return $jsr;
                }
                $i = $i + 1;
            }
            $categories = array_unique($categories); // remove duplicates if there hapens to be any

            // since everything has been verified, let's stick the project in first
            $sql = "INSERT INTO Course (Course_Number, Course_Name, Instructor, Estimated_Students, Designation_Name)
            VALUES ('" . $course_number . "', '" . $course_name . "', '" . $instructor . "', '" . $estimated_students . "', '" . $designation . "')";

            // if success, we need to add the categories and requirements
            if ($db->query($sql)) {
                // loop through all categories and add them to the join table one by one
                $length = count($categories);
                $c = 0;
                while ($c < $length) {
                    $sql = "INSERT INTO Course_Category (Course_Number, Category_Name)
                    VALUES ('" . $course_number . "', '" . $categories[$c] . "')";

                    if ($db->query($sql)) {
                        // on success do nothing
                    } else {
                        $jsr = new JsonResponse(array('error' => $db->error));
                        $jsr->setStatusCode(400);
                        return $jsr;
                    }
                    $c = $c + 1;
                }

                // if it reaches this point, we're done! return empty 201.
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
