<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of LeapYearController
 *
 * @author sbc
 */

namespace Kazist\Controller;

defined('KAZIST') or exit('Not Kazist Framework');

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Kazist\Model\KazistModel;
use Kazist\Model\BaseModel;
use Kazist\Event\ResponseEvent;
use Kazist\Service\Document\Document;

abstract class KazistController {

    /**
     *
     * @var object container 
     */
    protected $container;

    /**
     *
     * @var object doctrine 
     */
    protected $doctrine;

    /**
     *
     * @var object model : Setting the current active model to be used on this call. 
     */
    public $model;

    /**
     *
     * @var object model : Setting the current active model to be used on this call. 
     */
    public $model_class;

    /**
     *
     * @var $twig_paths paths
     */
    public $twig_paths = array();

    /**
     *
     * @var $doctrine_paths paths
     */
    public $doctrine_paths = array();

    /**
     *
     * @var  object request : For setting request object that will be used through out the system call.
     */
    public $request = '';

    /**
     * Function for setting doctrine
     * 
     * @param object $model
     * 
     */
    public function setDoctrine() {
        $this->doctrine = $this->getDoctrine();
    }

    /**
     * Function for setting current Model to be used
     * 
     * @param object $model
     * 
     */
    public function setModel($model = null) {

        $document = new Document($this->container, $this->request);

        if ($this->model_class <> '') {
            $model = new $this->model_class($this->doctrine, $this->request);

            $this->model = $model;
        } elseif (!is_object($model)) {

            $baseModel = new BaseModel($this->doctrine, $this->request);

            $this->model = $baseModel;
        } else {
            $this->model = $model;
        }

        if (!$this->container->has('document')) {
            $document_obj = $document->getDocument();
            $this->container->set('document', $document_obj);
        }

        $this->container->set('request', $this->request);

        $this->model->request = $this->request;
        $this->model->doctrine = $this->getDoctrine();
    }

    /*     * '
     * Function for setting container for specific call
     * 
     * @param ContainerInterface $container
     */

    public function setContainer(ContainerInterface $container) {
        $this->container = $container;
    }

    /**
     * Function for setting request object.
     * 
     * @param type $request
     */
    public function setRequest($request) {
        $this->request = $request;
    }

    /**
     * Function for bind data doctrine.
     * 
     * @param type $request
     */
    public function bindDataToEntity($entity, $data) {
        $this->model->bindDataToEntity($entity, $data);
    }

    public function securityFirewall() {

        $session = $this->container->get('session');
        $router = $this->request->attributes->get('_route');

        $is_granted = $this->model->denyAccessUnlessGranted();
        $user = $this->model->getUser();

        if ($this->model->checkTokenValid()) {

            if (!$is_granted) {
                if (is_object($user) && $user->id) {
                    $link_route = (WEB_IS_ADMIN) ? 'admin.home' : 'home';
                    $link_route = ($router == 'admin.home') ? 'home' : 'admin.home';
                    $this->specialRedirectToRoute($link_route);
                } else {
                    $link_route = (WEB_IS_ADMIN) ? 'admin.login' : 'login';
                    $this->specialRedirectToRoute($link_route);
                }
            } else {
                if (is_object($user) && $user->id) {
                    if (WEB_IS_ADMIN && !$user->is_admin) {
                        $link_route = 'home';
                        $this->addFlash('danger', 'You Dont have right to access this Area.');
                        $this->specialRedirectToRoute($link_route);
                    }
                }
            }
        } else {
            $link_route = 'home';
            $this->addFlash('danger', 'You Tokens is invalid. Fill and re-submit you form again.');
            $this->specialRedirectToRoute($link_route);
        }
    }

    public function initializeController() {

        $assets = $this->container->get('session')->get('kazist_assets');

        $assets['css']['core']['assets/css/jquery-ui.css'] = 'assets/css/jquery-ui.css';
        $assets['css']['core']['assets/css/bootstrap.css'] = 'assets/css/bootstrap.css';
        $assets['css']['core']['assets/css/font-awesome.css'] = 'assets/css/font-awesome.css';
        $assets['css']['core']['assets/css/jquery-ui.css'] = 'assets/css/jquery-ui.css';

        $assets['js']['core']['assets/js/jquery.js'] = 'assets/js/jquery.js';
        $assets['js']['core']['assets/js/jquery-ui.js'] = 'assets/js/jquery-ui.js';
        // $assets['js']['core']['assets/js/jqueryui-fix.js'] = 'assets/js/jqueryui-fix.js';
        $assets['js']['core']['assets/js/bootstrap.js'] = 'assets/js/bootstrap.js';
        $assets['js']['core']['assets/js/kazist.js'] = 'assets/js/kazist/kazist.js';
        $assets['js']['core']['assets/js/json2.js'] = 'assets/js/json2.js';


        $this->container->get('session')->set('kazist_assets', $assets);
    }

    /**
     * Function for rendering Twig Function Based on files path passed
     * 
     * @param string $template
     * @param array $objectList
     * 
     * @return string
     */
    public function render($template, Array $objectList) {

        $html = '';
        $kazistModel = new KazistModel();
        $document = $this->container->get('document');

        $default_view = $kazistModel->setDefaultView();

        if ($default_view <> '') {
            $this->twig_paths[] = $default_view;
        }

        if (WEB_IS_ADMIN) {
            if ($objectList['show_action']) {
                $document->action_content = $objectList['action_content'] = $kazistModel->renderData('Kazist:views:general:action.index.twig', $objectList, $this->twig_paths);

                $document->main_form_id = $objectList['main_form_id'] = str_replace('.', '_', $document->base_route) . '_' . $objectList['action_type'];
                $html .= '<input id="main_form_id" class="main_form_id" type="hidden" name="view" value="' . $document->main_form_id . '"/>';
            }
            if ($objectList['show_search']) {
                $document->search_content = $objectList['search_content'] = $kazistModel->renderData('Kazist:views:general:search.index.twig', $objectList, $this->twig_paths);
            }

            if ($objectList['show_action'] || $objectList['show_search']) {
                $document->action_search_content = $kazistModel->renderData('Kazist:views:general:action.search.twig', $objectList, $this->twig_paths);
                $html .= $document->action_search_content;
            }
        }

        if (isset($objectList['show_messages']) && $objectList['show_messages']) {
            $document->messages_content = $objectList['messages_content'] = $kazistModel->renderData('Kazist:views:general:messages.index.twig', $objectList, $this->twig_paths);
        }

        $html .= $kazistModel->renderData($template, $objectList, $this->twig_paths);

        return $html;
    }

    public function response($html) {

        $response = new Response($html);

        //$response->setTtl(10);

        $this->container->get('dispatcher')->dispatch('response', new ResponseEvent($response, $this->request));

        return $response;
    }

    /**
     * Function for adding Twig Directory to the templates array
     * 
     * @param string $file
     * 
     * @return string
     */
    public function addTemplatePath($file) {
        if (is_dir($file)) {
            $paths[] = $file;
        }
        return $paths;
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param string $route         The name of the route
     * @param mixed  $parameters    An array of parameters
     * @param int    $referenceType The type of reference (one of the constants in UrlGeneratorInterface)
     *
     * @return string The generated URL
     *
     * @see UrlGeneratorInterface
     */
    protected function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH) {

        $new_route_collection = $this->container->getParameter('routes');

        $url_generator = new UrlGenerator($new_route_collection, $this->container->get('context'));

        return $url_generator->generate($route, $parameters, $referenceType);
    }

    /**
     * Forwards the request to another controller.
     *
     * @param string $controller The controller name (a string like BlogBundle:Post:index)
     * @param array  $path       An array of path parameters
     * @param array  $query      An array of query parameters
     *
     * @return Response A Response instance
     */
    protected function forward($controller, array $path = array(), array $query = array()) {
        $path['_controller'] = $controller;
        $subRequest = $this->container->get('request_stack')->getCurrentRequest()->duplicate($query, null, $path);

        return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Returns a RedirectResponse to the given URL.
     *
     * @param string $url    The URL to redirect to
     * @param int    $status The status code to use for the Response
     *
     * @return RedirectResponse
     */
    protected function redirect($url, $status = 302) {

        $this->container->get('session')->set('is_redirect', true);

        return new RedirectResponse($url, $status);
    }

    protected function specialRedirectToRoute($route, array $parameters = array(), $status = 302) {
        $this->redirect($this->generateUrl($route, $parameters), $status)->sendHeaders();
        exit;
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     *
     * @param string $route      The name of the route
     * @param array  $parameters An array of parameters
     * @param int    $status     The status code to use for the Response
     *
     * @return RedirectResponse
     */
    protected function redirectToRoute($route, array $parameters = array(), $status = 302) {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    /**
     * Returns a JsonResponse that uses the serializer component if enabled, or json_encode.
     *
     * @param mixed $data    The response data
     * @param int   $status  The status code to use for the Response
     * @param array $headers Array of extra headers to add
     * @param array $context Context to pass to serializer when using serializer component
     *
     * @return JsonResponse
     */
    protected function json($data, $status = 200, $headers = array(), $context = array()) {

        if ($this->container->has('serializer')) {
            $json = $this->container->get('serializer')->serialize($data, 'json', array_merge(array(
                'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
                            ), $context));

            return new JsonResponse($json, $status, $headers, true);
        }

        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Adds a flash message to the current session for type.
     *
     * @param string $type    The type
     * @param string $message The message
     *
     * @throws \LogicException
     */
    protected function addFlash($type, $message) {
        if (!$this->container->has('session')) {
            throw new \LogicException('You can not use the addFlash method if sessions are disabled.');
        }

        $this->container->get('session')->getFlashBag()->add($type, $message);
    }

    /**
     * Checks if the attributes are granted against the current authentication token and optionally supplied object.
     *
     * @param mixed $attributes The attributes
     * @param mixed $object     The object
     *
     * @return bool
     *
     * @throws \LogicException
     */
    protected function isGranted($attributes, $object = null) {
        if (!$this->container->has('security.authorization_checker')) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        return $this->container->get('security.authorization_checker')->isGranted($attributes, $object);
    }

    /**
     * Throws an exception unless the attributes are granted against the current authentication token and optionally
     * supplied object.
     *
     * @param mixed  $attributes The attributes
     * @param mixed  $object     The object
     * @param string $message    The message passed to the exception
     *
     * @throws AccessDeniedException
     */
    protected function denyAccessUnlessGranted($attributes, $object = null, $message = 'Access Denied.') {
        if (!$this->isGranted($attributes, $object)) {
            throw $this->createAccessDeniedException($message);
        }
    }

    /**
     * Streams a view.
     *
     * @param string           $view       The view name
     * @param array            $parameters An array of parameters to pass to the view
     * @param StreamedResponse $response   A response instance
     *
     * @return StreamedResponse A StreamedResponse instance
     */
    protected function stream($view, array $parameters = array(), StreamedResponse $response = null) {
        if ($this->container->has('templating')) {
            $templating = $this->container->get('templating');

            $callback = function () use ($templating, $view, $parameters) {
                $templating->stream($view, $parameters);
            };
        } elseif ($this->container->has('twig')) {
            $twig = $this->container->get('twig');

            $callback = function () use ($twig, $view, $parameters) {
                $twig->display($view, $parameters);
            };
        } else {
            throw new \LogicException('You can not use the "stream" method if the Templating Component or the Twig Bundle are not available.');
        }

        if (null === $response) {
            return new StreamedResponse($callback);
        }

        $response->setCallback($callback);

        return $response;
    }

    /**
     * Returns a NotFoundHttpException.
     *
     * This will result in a 404 response code. Usage example:
     *
     *     throw $this->createNotFoundException('Page not found!');
     *
     * @param string          $message  A message
     * @param \Exception|null $previous The previous exception
     *
     * @return NotFoundHttpException
     */
    protected function createNotFoundException($message = 'Not Found', \Exception $previous = null) {
        return new NotFoundHttpException($message, $previous);
    }

    /**
     * Returns an AccessDeniedException.
     *
     * This will result in a 403 response code. Usage example:
     *
     *     throw $this->createAccessDeniedException('Unable to access this page!');
     *
     * @param string          $message  A message
     * @param \Exception|null $previous The previous exception
     *
     * @return AccessDeniedException
     */
    protected function createAccessDeniedException($message = 'Access Denied.', \Exception $previous = null) {
        return new AccessDeniedException($message, $previous);
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param string $type    The fully qualified class name of the form type
     * @param mixed  $data    The initial data for the form
     * @param array  $options Options for the form
     *
     * @return Form
     */
    protected function createForm($type, $data = null, array $options = array()) {
        return $this->container->get('form.factory')->create($type, $data, $options);
    }

    /**
     * Creates and returns a form builder instance.
     *
     * @param mixed $data    The initial data for the form
     * @param array $options Options for the form
     *
     * @return FormBuilder
     */
    protected function createFormBuilder($data = null, array $options = array()) {
        return $this->container->get('form.factory')->createBuilder(FormType::class, $data, $options);
    }

    /**
     * Shortcut to return the Doctrine Registry service.
     *
     * @return Registry
     *
     * @throws \LogicException If DoctrineBundle is not available
     */
    protected function getDoctrine() {

        if (!$this->container->has('doctrine')) {
            throw new \LogicException('The Doctrine is not registered in your application.');
        }

        $this->container->get('doctrine')->setEntitiesPath($this->doctrine_paths);

        return $this->container->get('doctrine');
    }

    /**
     * Get a user from the Security Token Storage.
     *
     * @return mixed
     *
     * @throws \LogicException If SecurityBundle is not available
     *
     * @see TokenInterface::getUser()
     */
    protected function getUser() {
        if (!$this->container->has('security.token_storage')) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
// e.g. anonymous authentication
            return;
        }

        return $user;
    }

    /**
     * Returns true if the service id is defined.
     *
     * @param string $id The service id
     *
     * @return bool true if the service id is defined, false otherwise
     */
    protected function has($id) {
        return $this->container->has($id);
    }

    /**
     * Gets a container service by its id.
     *
     * @param string $id The service id
     *
     * @return object The service
     */
    protected function get($id) {
        return $this->container->get($id);
    }

    /**
     * Gets a container configuration parameter by its name.
     *
     * @param string $name The parameter name
     *
     * @return mixed
     */
    protected function getParameter($name) {
        return $this->container->getParameter($name);
    }

    /**
     * Checks the validity of a CSRF token.
     *
     * @param string $id    The id used when generating the token
     * @param string $token The actual token sent with the request that should be validated
     *
     * @return bool
     */
    protected function isCsrfTokenValid($id, $token) {
        if (!$this->container->has('security.csrf.token_manager')) {
            throw new \LogicException('CSRF protection is not enabled in your application.');
        }

        return $this->container->get('security.csrf.token_manager')->isTokenValid(new CsrfToken($id, $token));
    }

}
