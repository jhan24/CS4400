<?php

namespace AppBundle\Listener;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Database;
use AppBundle\Controller\AuthenticationRequiredController;
use AppBundle\Controller\AdminRequiredController;
use AppBundle\Controller\ErrorController;

/**
 * This listener provides hooks for authentication checking before a
 * request is processed in a controller, and after a response is returned
 * by a controller.
 *
 * Provides checks for both basic authentication (logged in user) as well as
 * admin authentication (if user is admin). <- TODO
 */
class AuthenticationListener
{

    /**
    * Function called before a request is processed in any controller
    */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure.
         * This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            return;
        }

        // if the controller contains all rest endpoints that required a logged in user
        if ($controller[0] instanceof AuthenticationRequiredController) {
            $db = Database::getInstance();
            $request = $event->getRequest();
            $sendResponse = 0;
            // check for the valid cookies
            if ($request->cookies->has('cs4400-username') && $request->cookies->has('cs4400-password')) {
                $username = $request->cookies->get('cs4400-username');
                $password = $request->cookies->get('cs4400-password');

                // check the cookie's values to the actual DB values
                // in an actual authentication program, a new table storing the user's username and a session generated token
                // would be used rather than just storing the user's password in plain text in a cookie.
                // but this is for CS4400, so do we really care?
                $result = $db->query("SELECT * FROM User WHERE Username='" . $username . "' AND Password='" . $password . "'");
                if (!$result) {
                    $sendResponse = 1; // BAD CASE: INVALID SQL
                } else {
                    $data = $result->fetch_all(MYSQLI_ASSOC);
                    if ($result->num_rows < 1) {
                        $sendResponse = 1; // BAD CASE: INVALID CREDENTIALS
                    } else {
                        if ($data[0]['isAdmin'] == 0) {
                            // VALID CREDENTIALS - lets add them to the request
                            // attributes so that they're easily accessible.
                            $event->getRequest()->attributes->set('username', $username);
                            $event->getRequest()->attributes->set('password', $password);
                            $event->getRequest()->attributes->set('admin', 1);
                        } else {
                            $sendResponse = 2; // BAD CASE: USER IS AN ADMIN
                            // For some reason, in this project, admin users are not allowed to perform any student actions.
                        }
                    }
                }
            } else {
                $sendResponse = 1;
            }

            // if no cookies or cookie had invalid credentials, use the ErrorController
            // to return an error instead of processing the right endpoint.
            if ($sendResponse == 1) {
                $event->setController(array(new ErrorController(),'invalidCredentials'));
            } else if ($sendResponse == 2) {
                $event->setController(array(new ErrorController(),'notStudent'));
            }
        } else if ($controller[0] instanceof AdminRequiredController) {
            $db = Database::getInstance();
            $request = $event->getRequest();
            $sendResponse = 0;
            // check for the valid cookies
            if ($request->cookies->has('cs4400-username') && $request->cookies->has('cs4400-password')) {
                $username = $request->cookies->get('cs4400-username');
                $password = $request->cookies->get('cs4400-password');

                // check the cookie's values to the actual DB values
                // in an actual authentication program, a new table storing the user's username and a session generated token
                // would be used rather than just storing the user's password in plain text in a cookie.
                // but this is for CS4400, so do we really care?
                $result = $db->query("SELECT * FROM User WHERE Username='" . $username . "' AND Password='" . $password . "'");
                if (!$result) {
                    $sendResponse = 1; // BAD CASE: INVALID SQL
                } else {
                    $data = $result->fetch_all(MYSQLI_ASSOC);
                    if ($result->num_rows < 1) {
                        $sendResponse = 1; // BAD CASE: INVALID CREDENTIALS
                    } else {
                        if ($data[0]['isAdmin'] == 1) {
                            // VALID CREDENTIALS - lets add them to the request
                            // attributes so that they're easily accessible.
                            $event->getRequest()->attributes->set('username', $username);
                            $event->getRequest()->attributes->set('password', $password);
                            $event->getRequest()->attributes->set('admin', 1);
                        } else {
                            $sendResponse = 1; // BAD CASE: USER IS NOT AN ADMIN
                        }
                    }
                }
            } else {
                $sendResponse = 1;
            }

            // if no cookies or cookie had invalid credentials, use the ErrorController
            // to return an error instead of processing the right endpoint.
            if ($sendResponse == 1) {
                $event->setController(array(new ErrorController(),'notAdministrator'));
            }
        }
    }

    /**
    * Function called after a request is processed in any controller
    */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // does nothing, for now at least
    }
}
