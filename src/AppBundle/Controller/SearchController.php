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
 * This controller provides for the necessary searching and filtering
 * capability as required by the Main Page in the project.
 *
 * The controller only deals with one endpoint, but it's super complicated.
 * You normally would not do all of this through pure SQL.
 */
class SearchController extends FOSRestController implements AuthenticationRequiredController
{
    /**
    * GET: List of all valid projects and courses, with the filters you specify.
    * Include filters in the Request Parameters.
    * Valid options: Title, Designation, Major, Year, Category, Type, Department
    * Type must be either "Project", "Course", or "Both".
    * If there are multiple categories, they must be separated with a comma.
    *
    * @Get("/rest/search")
    */
    public function search(Request $request) {
        $db = Database::getInstance();
        $type = "Both"; // default search type includes both projects and courses
        $categoryArray = "Category.Category_Name)"; // default will return without filters on the Category
        $project_designation = "Project.Designation_Name"; // default will return without filters on the Designation
        $course_designation = "Course.Designation_Name"; // default will return without filters on the Designation
        $requirementFilter = 0; // currently no need for filtering on requirements - if necessary, a different SQL search is necessary that disregards the courses even in Type=Both.
        $title = ""; // default will have no filter for title
        $major = "null";
        $year = "null";
        $department = "null";

        // Go through the request parameters and overwrite any filters that have been given to us.
        if ($request->query->has('Type')) {
            $type = $request->query->get('Type');
        }
        if ($request->query->has('Category')) { // handles multiple categories here, generates a string with it to be inserted into the sql later
            $temp = $request->query->get('Category');
            $pieces = explode(",", $temp);
            $length = count($pieces);
            $i = 0;
            $categoryArray = "'";
            while ($i < $length) {
                $categoryArray = $categoryArray . trim($pieces[$i]) . "'";
                if ($i + 1 < $length) {
                    $categoryArray = $categoryArray . " OR Category.Category_Name='";
                } else {
                    $categoryArray = $categoryArray . ")";
                }
                $i = $i + 1;
            }
        }
        if ($request->query->has('Designation')) {
            $project_designation = "'" . $request->query->get('Designation') . "'";
            $course_designation = "'" . $request->query->get('Designation'). "'";
        }
        if ($request->query->has('Major')) {
            $major = $request->query->get('Major');
            $requirementFilter = 1;
        }
        if ($request->query->has('Year')) {
            $year = $request->query->get('Year');
            $requirementFilter = 1;
        }
        if ($request->query->has('Title')) {
            $title = $request->query->get('Title');
        }
        if ($request->query->has('Department')) {
            $title = $request->query->get('Department');
        }

        if (strcmp($type, 'Both') == 0) {
            if ($requirementFilter == 0) { // no filter based on requirement, so search in both
                // oh baby ---------------------------------------------------------------------------------------------------------------------------------------------------------------------
                $sql = "SELECT Project.Project_Name as 'Name', 'Project' as Type FROM Project
                INNER JOIN Project_Category ON Project.Project_Name = Project_Category.Project_Name
                INNER JOIN Category ON Project_Category.Category_Name = Category.Category_Name
                WHERE (Category.Category_Name=" . $categoryArray . " AND Project.Designation_Name=" . $project_designation . " AND Project.Project_Name LIKE '%" . $title . "%'
                UNION SELECT Course.Course_Name as 'Name', 'Course' as Type FROM Course
                INNER JOIN Course_Category ON Course.Course_Number = Course_Category.Course_Number
                INNER JOIN Category ON Course_Category.Category_Name = Category.Category_Name
                WHERE (Category.Category_Name=" . $categoryArray . " AND Course.Designation_Name=" . $course_designation . " AND Course.Course_Name LIKE '%" . $title . "%'";
                //  ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                $sql = str_replace("\n", " ", $sql);
                $sql = str_replace("\r", " ", $sql);

                if(!$result = $db->query($sql)){
                    $jsr = new JsonResponse(array('error' => $db->error));
                    $jsr->setStatusCode(400);
                    return $jsr;
                }

                $result = $db->query($sql);
                $data = $result->fetch_all(MYSQLI_ASSOC);
                return new JsonResponse(array('num_rows' => $result->num_rows, 'data' => $data, 'sql' => $sql));
            } else { // filter required based on requirement, so only search in project
                // oh baby ---------------------------------------------------------------------------------------------------------------------------------------------------------------------
                $sql = "SELECT DISTINCT(Project.Project_Name) as 'Name', 'Project' as Type FROM Project
                INNER JOIN Project_Category ON Project.Project_Name = Project_Category.Project_Name
                INNER JOIN Category ON Project_Category.Category_Name = Category.Category_Name
                LEFT JOIN Requirement ON Project.Project_Name = Requirement.Project_Name
                WHERE (Category.Category_Name=" . $categoryArray . " AND Project.Designation_Name=" . $project_designation . " AND Project.Project_Name LIKE '%" . $title . "%'
                AND (Requirement.Requirement = '" . $major . "' OR Requirement.Requirement = '" . $year . "' OR Requirement.Requirement = '" . $department . "')";
                //  ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                $sql = str_replace("\n", " ", $sql);
                $sql = str_replace("\r", " ", $sql);
                if(!$result = $db->query($sql)){
                    $jsr = new JsonResponse(array('error' => $db->error));
                    $jsr->setStatusCode(400);
                    return $jsr;
                }

                $result = $db->query($sql);
                $data = $result->fetch_all(MYSQLI_ASSOC);
                return new JsonResponse(array('num_rows' => $result->num_rows, 'data' => $data, 'sql' => $sql));
            }
        } else if (strcmp($type, 'Project') == 0) { // only searching for projects
            // oh baby ---------------------------------------------------------------------------------------------------------------------------------------------------------------------
            $sql = "SELECT DISTINCT(Project.Project_Name) as 'Name', 'Project' as Type FROM Project
            INNER JOIN Project_Category ON Project.Project_Name = Project_Category.Project_Name
            INNER JOIN Category ON Project_Category.Category_Name = Category.Category_Name
            LEFT JOIN Requirement ON Project.Project_Name = Requirement.Project_Name
            WHERE (Category.Category_Name=" . $categoryArray . " AND Project.Designation_Name=" . $project_designation . " AND Project.Project_Name LIKE '%" . $title . "%'";
            //  ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            if ($requirementFilter == 1) {
                $sql = $sql . " AND (Requirement.Requirement = '" . $major . "' OR Requirement.Requirement = '" . $year . "' OR Requirement.Requirement = '" . $department . "')";
            }
            $sql = str_replace("\n", " ", $sql);
            $sql = str_replace("\r", " ", $sql);
            if(!$result = $db->query($sql)){
                $jsr = new JsonResponse(array('error' => $db->error));
                $jsr->setStatusCode(400);
                return $jsr;
            }

            $result = $db->query($sql);
            $data = $result->fetch_all(MYSQLI_ASSOC);
            return new JsonResponse(array('num_rows' => $result->num_rows, 'data' => $data, 'sql' => $sql));
        } else if (strcmp($type, 'Course') == 0) { // course only (if requirements are selected, return literally nothing)
            // oh baby ---------------------------------------------------------------------------------------------------------------------------------------------------------------------
            $sql = "SELECT DISTINCT(Course.Course_Name) as 'Name', 'Course' as Type FROM Course
            INNER JOIN Course_Category ON Course.Course_Number = Course_Category.Course_Number
            INNER JOIN Category ON Course_Category.Category_Name = Category.Category_Name
            WHERE (Category.Category_Name=" . $categoryArray . " AND Course.Designation_Name=" . $course_designation . " AND Course.Course_Name LIKE '%" . $title . "%'";
            //  ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            if ($requirementFilter == 1) {
                $sql = $sql . " AND Category.Category_Name = 'Invalid Category Name To Brute Force No Results'";
            }
            $sql = str_replace("\n", " ", $sql);
            $sql = str_replace("\r", " ", $sql);
            if(!$result = $db->query($sql)){
                $jsr = new JsonResponse(array('error' => $db->error));
                $jsr->setStatusCode(400);
                return $jsr;
            }

            $result = $db->query($sql);
            $data = $result->fetch_all(MYSQLI_ASSOC);
            return new JsonResponse(array('num_rows' => $result->num_rows, 'data' => $data, 'sql' => $sql));
        } else {
            $jsr = new JsonResponse(array('error' => 'Invalid search type specified.'));
            $jsr->setStatusCode(400);
            return $jsr;
        }

    }
}
