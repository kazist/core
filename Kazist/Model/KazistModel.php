<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of LeapYear
 *
 * @author sbc
 */

namespace Kazist\Model;

defined('KAZIST') or exit('Not Kazist Framework');

use Kazist\Service\Email\Email;
use Kazist\Service\Json\Json;
use Assetic\AssetManager;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Kazist\Service\Database\Query;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class KazistModel {

    public $doctrine = '';
    public $request = '';
    public $container = '';
    public $query_failed_attempt = 0;

    public function __construct($doctrine = '', $request = '') {

        global $sc;

        $this->container = $sc;
        $this->doctrine = $this->container->get('doctrine');
        $this->request = $this->container->get('request');
        ;
    }

    /* xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx Check Double Auth xxxxxxxxxxxxxxxx */

    public function verifyDoubleAuth($user, $router, $return_url) {

        $email = new Email();

        $session = $this->container->get('session');
        $doubleauth_code = $session->get('doubleauth_code');
        $user_doubleauth_code = $session->get('user_doubleauth_code');

        $route = $this->fetchDoubleAuth($router);

        if (is_object($route)) {

            if ($doubleauth_code <> '' && $user_doubleauth_code == $doubleauth_code) {
                return true;
            } else {
                $link_route = (WEB_IS_ADMIN) ? 'admin.doubleauth' : 'doubleauth';

                $parameters = array();
                $parameters['user'] = $user;
                $parameters['doubleauth_code'] = $doubleauth_code = rand(10000, 100000);
                $parameters['doubleauth_url'] = $this->generateUrl($link_route, array('return_url' => base64_encode($return_url)), 0);

                $email->sendDefinedLayoutEmail('users.users.doubleauth', $user->email, $parameters);
                $session->set('doubleauth_code', $doubleauth_code);
                $this->enqueueMessage($doubleauth_code);

                return false;
            }
        } else {
            return true;
        }
    }

    public function fetchDoubleAuth($router) {


        $query = new Query();
        $query->select('udr.*');
        $query->from('#__users_doubleauth_routes', 'udr')->where('udr.route=:route')->setParameter('route', $router);

        try {
            $route = $query->loadObject();
        } catch (\Exception $ex) {

            $this->doctrine->refresh = true;
            $this->doctrine->entity_path = JPATH_ROOT . 'applications/Users/Doubleauth/Routes/Code/Tables';
            $this->doctrine->getEntityManager();

            $route = $query->loadObject();
        }


        return $route;
    }

    /* xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx Start Process User Object xxxxxxxxxxxxxxxx */

    public function getUser() {

        $session = $this->container->get('session');
        $token = $session->get('security.token');

        if (is_object($token)) {

            $user = $token->getUser();

            if (is_object($user)) {

                $query = new Query();
                $query->select('uu.*');
                $query->from('#__users_users', 'uu');
                $query->where('uu.id=:id');
                $query->setParameter('id', $user->id);
                $temp_user = $query->loadObject();

                $user = (object) array_merge((array) $temp_user, (array) $user);

                $this->getUserGroupIds($user);
                $this->getUserRolesIds($user);
            }
        } else {
            $user = new \stdClass();
            $user->name = 'anon';
            $user->username = 'anon';
            $user->user_id = 0;
        }

        return $user;
    }

    private function getUserGroupIds($user) {

        $tmp_array = array();

        $query = new Query();
        $query->select('ug.*');
        $query->from('#__users_users_groups', 'uug');
        $query->leftJoin('uug', '#__users_groups', 'ug', 'ug.id=uug.group_id');
        $query->where('uug.user_id=:user_id');
        $query->setParameter('user_id', (int) $user->id);
        $groups = $query->loadObjectList();

        foreach ($groups as $key => $group) {
            if ($group->id) {
                $tmp_array[] = $group->id;
            }
        }

        $user->group_ids = $tmp_array;
        $user->groups = $groups;
    }

    private function getUserRolesIds($user) {
        $tmp_array = array();

        $query = new Query();
        $query->select('DISTINCT ur.*');
        $query->from('#__users_roles', 'ur');
        $query->leftJoin('ur', '#__users_users_roles', 'uur', 'ur.id=uur.role_id');
        $query->where('uur.user_id=:user_id');
        $query->setParameter('user_id', (int) $user->id);
        $user_roles = $query->loadObjectList();


        $query = new Query();
        $query->select(' DISTINCT ur.*');
        $query->from('#__users_roles', 'ur');
        $query->leftJoin('ur', '#__users_groups_roles', 'ugr', 'ur.id=ugr.role_id');

        $group_ids_str = (!empty($user->group_ids)) ? implode(',', $user->group_ids) : '';

        if ($group_ids_str != '') {
            $query->where('ugr.group_id IN (' . $group_ids_str . ')');
        } else {
            $query->where('1=-1');
        }

        $group_roles = $query->loadObjectList();

        $roles = array_merge($group_roles, $user_roles);

        foreach ($roles as $key => $role) {
            $tmp_array[] = $role->id;
        }

        $user->role_ids = $tmp_array;
        $user->roles = $roles;
    }

    /* xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx End Process User Object xxxxxxxxxxxxxxxx */

    /* xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx Start Process deny access xxxxxxxxxxxxxxxx */

    public function checkTokenValid() {

        $request = $this->container->get('request');
        $session = $this->container->get('session');
        $session_token = $session->get('_token');

        $form_token = $request->get('_token');
        $request_method = $request->server->get('REQUEST_METHOD');

        if ($session_token == '') {
            $session->set('_token', uniqid());
        }

        if ($request_method == 'GET' || ($request_method == 'POST' && $session_token == $form_token)) {
            return true;
        } else {
            return false;
        }
    }

    public function denyAccessUnlessGranted() {


        $exempt_route = array('', 'login', 'admin.login', 'admin.login_check', 'login_check',
            'admin.system.extensions.install', 'system_install', 'user_verification', 'resend_verification',
            'change_password', 'forgot_password');

        $router = $this->request->attributes->get('_route');

        $document = $this->container->get('document');
        $user = $document->user;

        $admin_access = false;

        foreach ($user->roles as $key => $role) {
            if ($role->admin_access) {
                $admin_access = true;
                break;
            }
        }

        if ($admin_access && ($router == 'admin.home' || $router == 'admin.home.slash')) {
            return true;
        }

        if (in_array($router, $exempt_route)) {
            return true;
        }

        if ($user->is_admin) {
            return true;
        }


        if (($document->login_required || WEB_IS_ADMIN)) {
            $is_allowed = $this->getUserActiveRole($document->unique_name, $user, $document->permissions);

            return $is_allowed;
        } else {
            return true;
        }

        return false;
    }

    public function getUserActiveRole($route_name, $user, $permissions) {

        $where_arr = array();

        $permissions_arr = explode(',', str_replace(' ', '', trim($permissions)));

        $query = new Query();
        $query->select(' DISTINCT srp.*');
        $query->from('#__users_permission', 'srp');
        if (!empty($user->role_ids)) {
            $query->andWhere('srp.role_id IN (' . implode(',', $user->role_ids) . ')');
        } else {
            $query->andWhere('1=-1');
        }
        $query->andWhere('srp.route =' . (int) $route_name);

        if (!empty($permissions_arr)) {
            foreach ($permissions_arr as $permission_item) {
                if ($permission_item <> '' && substr($permission_item, 0, 3) === "can") {
                    $where_arr[] = 'srp.' . $permission_item . ' = 1';
                }
            }
        }

        if (!empty($where_arr)) {
            $where_str = implode(' OR ', $where_arr);
            $query->andWhere($where_str);
        } else {
            $query->andWhere('1=-1');
        }

        $roles = $query->loadObjectList();

        return (count($roles)) ? true : false;
    }

    /* xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx Start Process deny access xxxxxxxxxxxxxxxx */

    public function enqueueMessage($message, $type = 'info') {

        $this->container->get('session')->getFlashBag()->add($type, $message);
    }

    public function makeDir($dir) {
        if (!is_dir($dir)) {
            $oldmask = umask(0);
            mkdir($dir, 0775, true);
            umask($oldmask);
        }
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
    public function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH, $data = array()) {

        $params_arr = array();
        $tmp_data = json_decode(json_encode($data), true);
        $tmp_parameters = json_decode(json_encode($parameters), true);
        $data_arr = array_merge((array) $tmp_data, (array) $tmp_parameters);

        if ($route == '') {
            return $route;
        }

        $new_route_collection = $this->container->getParameter('routes');
        $route_obj = $new_route_collection->get($route);


        if (method_exists($route_obj, 'getDefaults')) {

            $defaults = $route_obj->getDefaults();

            unset($defaults['_controller']);

            if (!empty($defaults)) {
                foreach ($defaults as $key => $default) {
                    $params_arr[$key] = ($data_arr[$key] <> '') ? $data_arr[$key] : $default;
                }
            }
        }

        if (!empty($parameters)) {
            foreach ($parameters as $key => $parameter) {
                $params_arr[$key] = ($data_arr[$key] <> '') ? $data_arr[$key] : $default;
            }
        }


        $url_generator = new UrlGenerator($new_route_collection, $this->container->get('context'));

        return $url_generator->generate($route, $params_arr, $referenceType);
    }

    public function getControllerResponse($controller_class, $function_name = 'indexAction', $params = array(), $twig_paths = '') {
        $result = '';
        $factory = new \Kazist\KazistFactory;
        $new_params = array();

        try {
            $controller = new $controller_class();
            $controller->twig_paths = $twig_paths;
            $controller->setContainer($this->container);
            $controller->setRequest($this->request);
            $controller->setDoctrine();
            $controller->setModel();
            $controller->securityFirewall();
            $controller->initializeController();

            $result = call_user_func_array(array($controller, $function_name), $params);

            return $result;
        } catch (\Exception $ex) {
            print_r($ex->getMessage());
            exit;
            $factory->enqueueMessage($ex->getMessage());
            throw $ex;
        }

        //  $result = $controller->loginAction($this->request);
    }

    public function buildForm() {
        
    }

    public function createFormBuilder() {

        // Set up the CSRF Token Manager
        $csrfTokenManager = new CsrfTokenManager();

        // Set up the Validator component
        $validator = Validation::createValidator();

        // Set up the Form component
        $formFactory = Forms::createFormFactoryBuilder()
                ->addExtension(new CsrfExtension($csrfTokenManager))
                ->addExtension(new ValidatorExtension($validator))
                ->addExtension(new HttpFoundationExtension())
                ->getFormFactory();

        return $formFactory;
    }

    public function setAssets($file) {

        $core = new AssetCollection(array(new FileAsset($file)));

        //  $core->load();
        //echo $core->dump();
    }

    /**
     * Function for rendering twig From string. Used in emails and No Controller Viewers
     * 
     * @param string $html
     * @param array $object_arr
     * @return string
     */
    public function renderString($html, $object_arr) {

        $object_arr = json_decode(json_encode($object_arr), true);
        $object_arr = (is_array($object_arr)) ? $object_arr : array();

        $twig = $this->getTwigObject($object_arr);

        $template = $twig->createTemplate($html);

        return $template->render($object_arr);
    }

    /**
     * Function for Rendering template based on object and file provided
     * 
     * @param string $template
     * @param array $object_arr
     * @param array $paths
     * @return string
     */
    public function renderData($raw_template, $object_arr, $paths = array()) {

        $is_found = false;
        $factory = new \Kazist\KazistFactory();
        $controller = $this->request->attributes->get('_controller');
        $template = str_replace(':', ';', $raw_template);

        $controller_arr = explode('Controllers', $controller);
        $template_arr = array_reverse(explode(';', $template));
        $template_name = $template_arr[0];
        $tmp_template = $template;

        $location = (WEB_IS_ADMIN) ? 'admin;' : '';
        $type = (isset($object_arr['action_type']) <> '') ? $object_arr['action_type'] . '.' : '';
        $new_template_name = ($template_name == 'action.index.twig' || $template_name == 'search.index.twig') ? $type . $template_name : $template_name;

        $new_template = str_replace('\\', ';', $controller_arr[0]) . 'views;' . $location . $new_template_name;

        if (file_exists(JPATH_ROOT . '/include/' . str_replace(';', '/', $tmp_template))) {
            $template = str_replace(';', '/', $tmp_template);
            $is_found = true;
        }

        if (file_exists(JPATH_ROOT . '/applications/' . str_replace(';', '/', $tmp_template))) {
            $template = str_replace(';', '/', $tmp_template);
            $is_found = true;
        }

        if (file_exists(JPATH_TEMPLATES . '/' . $tmp_template)) {
            $template = $tmp_template;
            $is_found = true;
        }

        if (file_exists(JPATH_ROOT . '/applications/' . str_replace(';', '/', $new_template))) {
            $template = str_replace(';', '/', $new_template);
            $is_found = true;
        }

        if (file_exists(JPATH_TEMPLATES . '/' . $new_template)) {
            $template = $new_template;
            $is_found = true;
        }

        if (!$is_found) {
            $template_name_arr = explode('.', $template_name);
            $template = 'Kazist/views/' . $template_name_arr[0] . '/' . $template_name;
        }

        $factory->loggingMessage($template);

        $twig = $this->getTwigObject($object_arr, $paths);

        $context = $twig->getContextData();

        $this->container->get('session')->set('twig_context', $context);

        $html = $twig->render($template);

        return $html;
    }

    /**
     * Function for Preparing Twig Object
     * 
     * @param array $object_arr
     * @param array $paths
     * @return \Kazist\Model\Twig
     */
    private function getTwigObject($object_arr, $paths = '') {

        // $twig = new Twig($config)

        $twig = $this->container->get('twig');

        if ($paths !== '') {
            $twig->setTemplatesPaths($paths, true);
        }

        foreach ($object_arr as $key => $object) {
            $twig->set($key, $object);
        }

        return $twig;
    }

    /**
     * Function for setting Default View Path to be used by twig
     * 
     * @return string
     */
    public function setDefaultView() {

        $dir = '';

        $document = $this->container->get('document');

        $dir = JPATH_ROOT . 'applications/' . $document->extension_path . '/Code/views';

        if (is_dir($dir)) {
            return $dir;
        } else {
            return false;
        }
    }

    public function configObject($file) {
        $json = new Json;
        return $json->configObject($file);
    }

    public function jsonStructure($file) {

        $json = new Json;

        $structure = $json->configObject($file);
 
        foreach ($structure['fields'] as $key => $field) {
            $structure['fields'][$field['name']] = $field;
            unset($structure['fields'][$key]);
        }


        return $structure;
    }

    public function loggingException($exception) {

        $baseModel = new BaseModel();
        if ($this->container->getParameter('system.logging')) {
            $msg = $exception->getMessage() . ' ' . $exception->getFile() . ' ' . $exception->getLine();

            if ($this->container->getParameter('system.debugging')) {

                $log = new Logger('kazist');
                $log->pushHandler(new StreamHandler($error_directory . 'kazist-error.log', Logger::WARNING));

                $this->container->get('debugger')['exceptions']->setChainExceptions(true);
                $this->container->get('debugger')['exceptions']->addException($exception);

                switch ($type) {
                    case 'error':
                        $log->error($msg);
                        break;
                    case 'warning':
                        $log->warning($msg);
                        break;

                    default:
                        $log->alert($msg);
                        break;
                }
            } else {
                $this->loggingMessage($msg, 'error');
            }
        }
    }

    public function loggingMessage($msg, $type = '') {

        if ($this->container->getParameter('system.logging')) {
            $error_directory = JPATH_ROOT . 'error_log/';

            $this->makeDir($error_directory);

            $log = new Logger('kazist');
            $log->pushHandler(new StreamHandler($error_directory . 'kazist-error.log', Logger::WARNING));


            if ($this->container->getParameter('system.debugging')) {
                $this->container->get('debugger')['messages']->info($msg);
            } else {
                switch ($type) {
                    case 'error':
                        $log->error($msg);
                        break;
                    case 'warning':
                        $log->warning($msg);
                        break;

                    default:
                        $log->alert($msg);
                        break;
                }
            }
        }
    }

}
