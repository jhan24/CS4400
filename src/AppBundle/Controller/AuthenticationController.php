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
 * This controller provides for basic authentication for this program.
 * The authentication system here is simple and it works, but needs
 * several fixes if it were to actually be used in production.
 */
class AuthenticationController extends FOSRestController
{
    /**
     * POST: Returns a cookie for authentication use for the logged-in user.
     * Must provide Username and Password in the post body of the request.
     * If valid credentials are provided, two cookies will be returned to the browser.
     * One contains the user's username, the other has the password in plain text.
     * See why this needs fixing?
     *
     * This endpoint should be used for login.
     *
     * @POST("/rest/authenticate")
     */
    public function createAuthentication(Request $request) {
        $db = Database::getInstance();
        if ($request->request->has('Username') && $request->request->has('Password')) {
            $username = $request->request->get('Username');
            $password = $request->request->get('Password');
            $result = $db->query("SELECT * FROM User WHERE Username='" . $username . "' AND Password='" . $password . "'");
            if (!$result) {
                $jsr = new JsonResponse(array('error' => $db->error));
                $jsr->setStatusCode(400);
                return $jsr;
            }
            $data = $result->fetch_all(MYSQLI_ASSOC);
            if ($result->num_rows < 1) {
                $jsr = new JsonResponse(array('error' => 'Invalid Credentials.'));
                $jsr->setStatusCode(400);
                return $jsr;
            }
            $response = new Response();
            $response->headers->setCookie(new Cookie('cs4400-username', $username));
            $response->headers->setCookie(new Cookie('cs4400-password', $password));
            $response->setStatusCode(200);
            return $response;
        } else {
            $jsr = new JsonResponse(array('error' => 'Required parameters are missing.'));
            $jsr->setStatusCode(400);
            return $jsr;
        }
    }

    /**
    * GET: Obtains the user information for the logged in username.
    * This endpoint should be used as a check to see if the user is logged in.
    * If it returns a non-200 status code, redirect the user to the login screen.
    *
    * @GET("/rest/authenticate")
    */
    public function checkAuthentication(Request $request) {
        $db = Database::getInstance();
        if ($request->cookies->has('cs4400-username') && $request->cookies->has('cs4400-password')) {
            $username = $request->cookies->get('cs4400-username');
            $password = $request->cookies->get('cs4400-password');
            $result = $db->query("SELECT * FROM User WHERE Username='" . $username . "' AND Password='" . $password . "'");
            if (!$result) {
                $jsr = new JsonResponse(array('error' => $db->error));
                $jsr->setStatusCode(400);
                return $jsr;
            }
            $data = $result->fetch_all(MYSQLI_ASSOC);
            if ($result->num_rows < 1) {
                $jsr = new JsonResponse(array('error' => 'Invalid Credentials.'));
                $jsr->setStatusCode(400);
                return $jsr;
            }
            $response = new Response();
            $response->setStatusCode(200);
            return $response;
        }
        $jsr = new JsonResponse(array('error' => 'Not authenticated.'));
        $jsr->setStatusCode(403);
        return $jsr;

    }

    /**
    * DELETE: Deletes the client's cookies
    * This endpoint should be used for logout.
    * Our cookies also have no expiration date...
    * @DELETE("/rest/authenticate")
    */
    public function destroyAuthentication(Request $request) {
        $response = new Response();
        $response->headers->clearCookie('cs4400-username');
        $response->headers->clearCookie('cs4400-password');
        $response->send();
    }
}
