<?php

/*
 * This file is part of Kazist Framework.
 * (c) Dedan Irungu <irungudedan@gmail.com>
 *  For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 * 
 */

namespace Kazist\Service;

/**
 * Description of ContainerAwareControllerResolver
 *
 * @author sbc
 */
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpFoundation\Request;
use Kazist\KazistFactory;

class ContainerAwareControllerResolver extends ControllerResolver {

    private $container;
    public $request;

    public function __construct(LoggerInterface $logger = null, ContainerInterface $container = null) {

        parent::__construct($logger);

        $this->container = $container;
    }

    public function getController(Request $request) {

        $this->request = $request;
        return parent::getController($request);
    }

    public function instantiateController($class) {

        $new_class = new $class();

        $this->container->register('request', $this->request);
        
        $this->setDefaultParameters();
        $new_class->setContainer($this->container);
        $new_class->setRequest($this->request);
        $new_class->setDoctrine();
        $new_class->model_class = $this->getModelClass($this->request);
        $new_class->setModel();

        $new_class->securityFirewall();
        $new_class->initializeController();

        return $new_class;
    }

    public function getModelClass($request) {

        $class_arr = array();

        $controller = $request->attributes->get('_controller');
        $controller_arr = explode('Code', $controller);

        if (count($controller_arr) > 1) {
            $class_arr = $initial_arr = array_filter(explode('\\', $controller_arr[0]));
          
            $class_arr[] = 'Code';
            $class_arr[] = 'Models';
            $class_arr[] = end($initial_arr) . 'Model';
        }

        $class_name = implode('\\', $class_arr);

        if (class_exists($class_name)) {
            return $class_name;
        } else {
            return '';
        }
    }

    private function setDefaultParameters() {

        $router = $this->request->attributes->get('_route');
        $request_url = $this->request->server->get('REQUEST_URI');
        $http_host = $this->request->server->get('HTTP_HOST');
        $request_scheme = $this->request->server->get('REQUEST_SCHEME');
        $request_scheme = ($request_scheme <> '') ? $request_scheme : 'http';

        $web_base = $request_scheme . '://' . $http_host . rtrim($this->request->getBaseUrl(), '/');
        $web_root = trim(str_replace(array('index.php', 'index-dev.php'), '', $web_base), '/') . '/';

        if (!defined('REQUEST')) {
            define('REQUEST', $this->request);
        }

        if (!defined('WEB_ROOT')) {
            define('WEB_ROOT', $web_root);
        }

        if (!defined('WEB_BASE')) {
            define('WEB_BASE', $web_base);
        }

        if (!defined('WEB_FRONT_HOME')) {
            define('WEB_FRONT_HOME', $web_base);
        }

        if (!defined('WEB_ADMIN_HOME')) {
            define('WEB_ADMIN_HOME', $web_base . '/admin');
        }

        if (!defined('WEB_IS_ADMIN')) {

            $path_info = $this->request->getPathInfo();

            $path_info_arr = explode('/', $path_info);
            $path_info_arr = array_values(array_filter($path_info_arr));

            $web_is_admin = ($path_info_arr[0] == 'admin') ? true : false;

            define('WEB_IS_ADMIN', $web_is_admin);
        }

        if (!defined('WEB_IS_HOMEPAGE')) {

            $web_is_homepage = ($router == 'home' || $router == 'admin.home') ? true : false;

            define('WEB_IS_HOMEPAGE', $web_is_homepage);
        }


        if (!defined('WEB_HOME')) {
            $extend = (WEB_IS_ADMIN) ? '/admin' : '';

            define('WEB_HOME', $web_base . $extend);
        }

        if (!defined('JPATH_TEMPLATES')) {
            define('JPATH_TEMPLATES', $this->getTemplate());
        }
    }

    public function getTemplate() {

        $factory = new KazistFactory();


        $viewside = (WEB_IS_ADMIN) ? 'backend' : 'frontend';
        $where_arr = array('is_default=:is_default', 'viewside=:viewside', 'published=:published');
        $parameter_arr = array('is_default' => 1, 'viewside' => $viewside, 'published' => 1);
        $template = $factory->getRecord('#__system_templates', 'st', $where_arr, $parameter_arr);

        $path = JPATH_ROOT . 'themes/' . $template->viewside . '/' . $template->name . '/views';

        return (is_dir($path)) ? $path : $this->getDefaultTemplate();
    }

    public function getDefaultTemplate() {

        if (WEB_IS_ADMIN) {
            $path = JPATH_ROOT . 'themes/backend/adminlte/views';
        } else {
            $path = JPATH_ROOT . 'themes/frontend/awesome/views';
        }

        return $path;
    }

}
