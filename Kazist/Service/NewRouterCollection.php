<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Kazist\Service;

defined('KAZIST') or exit('Not Kazist Framework');

use Symfony\Component\Routing;
use Symfony\Component\Routing\RouteCollection;
use Kazist\StringModification;

/**
 * Class for implementing Custom Route Collection
 */
class NewRouterCollection extends RouteCollection {

    public $prefix = '';

    public function __construct() {

        /* xxxxxxxxxxxxxxxxxxxxxxxxxxx Homepage xxxxxxxxxxxxxxxxxxxxxxxxxx */

        $this->add('home', new Routing\Route('', array(
            '_controller' => 'Kazist\Controller\\HomeController::indexAction',
        )));
        $this->add('home', new Routing\Route('/', array(
            '_controller' => 'Kazist\Controller\\HomeController::indexAction',
        )));
        $this->add('admin.home', new Routing\Route('/admin', array(
            '_controller' => 'Kazist\Controller\\HomeController::indexAction',
        )));
        $this->add('admin.home.slash', new Routing\Route('/admin/', array(
            '_controller' => 'Kazist\Controller\\HomeController::indexslashAction',
        )));

        /* xxxxxxxxxxxxxxxxxxxxxxxxxxx User Management xxxxxxxxxxxxxxxxxxxxxxxxxx */

        $this->add('user_verification', new Routing\Route('/user-verification', array(
            '_controller' => 'Kazist\Controller\\UserController::verificationAction',
        )));

        $this->add('registration', new Routing\Route('/registration', array(
            '_controller' => 'Kazist\Controller\\UserController::registerAction',
        )));

        $this->add('register', new Routing\Route('/register', array(
            '_controller' => 'Kazist\Controller\\UserController::registerAction',
        )));

        $this->add('loginregister', new Routing\Route('/loginregister', array(
            '_controller' => 'Kazist\Controller\\UserController::loginregisterAction',
        )));

        $this->add('registration_save', new Routing\Route('/registration-save', array(
            '_controller' => 'Kazist\Controller\\UserController::saveregistrationAction',
        )));

        $this->add('registration_thankyou', new Routing\Route('/registration-thankyou', array(
            '_controller' => 'Kazist\Controller\\UserController::thankyouAction',
        )));

        $this->add('resend_verification_form', new Routing\Route('/resend-verification-form', array(
            '_controller' => 'Kazist\Controller\\UserController::resendFormAction',
        )));

        $this->add('resend_verification', new Routing\Route('/resend-verification', array(
            '_controller' => 'Kazist\Controller\\UserController::resendAction',
        )));

        $this->add('change_password_form', new Routing\Route('/change-password-form', array(
            '_controller' => 'Kazist\Controller\\UserController::changeFormAction',
        )));

        $this->add('change_password', new Routing\Route('/change-password', array(
            '_controller' => 'Kazist\Controller\\UserController::changeAction',
        )));

        $this->add('forgot_password_form', new Routing\Route('/forgot-password-form', array(
            '_controller' => 'Kazist\Controller\\UserController::forgotFormAction',
        )));

        $this->add('forgot_password', new Routing\Route('/forgot-password', array(
            '_controller' => 'Kazist\Controller\\UserController::forgotAction',
        )));

        $this->add('logout', new Routing\Route('/logout', array(
            '_controller' => 'Kazist\Controller\\UserController::logoutAction',
        )));

        $this->add('login', new Routing\Route('/login', array(
            '_controller' => 'Kazist\Controller\\UserController::loginAction',
        )));

        $this->add('admin.login', new Routing\Route('/admin/login', array(
            '_controller' => 'Kazist\Controller\\UserController::loginAction',
        )));

        $this->add('login_check', new Routing\Route('/login-check', array(
            '_controller' => 'Kazist\Controller\\UserController::loginCheckAction',
        )));

        $this->add('admin.login_check', new Routing\Route('/admin/login-check', array(
            '_controller' => 'Kazist\Controller\\UserController::loginCheckAction',
        )));

        $this->add('user_invite', new Routing\Route('/invite/{username}', array(
            'username' => null,
            '_controller' => 'Kazist\Controller\\UserController::userInviteAction',
        )));

        /* xxxxxxxxxxxxxxxxxxxxxxxxxxx Generator Management xxxxxxxxxxxxxxxxxxxxxxxxxx */

        $this->add('generator', new Routing\Route('/generator', array(
            '_controller' => 'Generator\\Code\\Controllers\\GeneratorController::indexAction',
        )));

        $this->add('generator_create', new Routing\Route('/generator-create', array(
            '_controller' => 'Generator\\Code\\Controllers\\GeneratorController::createAction',
        )));

        $this->add('generator_show', new Routing\Route('/generator-show/{id}', array(
            'id' => null,
            '_controller' => 'Generator\\Code\\Controllers\\GeneratorController::showAction',
        )));
        $this->add('generator_builder', new Routing\Route('/generator-build/{id}{file}', array(
            'id' => null,
            'file' => null,
            '_controller' => 'Generator\\Code\\Controllers\\GeneratorController::buildAction',
        )));

        $this->add('generator_save', new Routing\Route('/generator-save/{type}', array(
            'type' => null,
            '_controller' => 'Generator\\Code\\Controllers\\GeneratorController::saveAction',
        )));

        $this->add('generator_table', new Routing\Route('/generator-table/{id}/{table}', array(
            'id' => null,
            'table' => null,
            '_controller' => 'Generator\\Code\\Controllers\\GeneratorController::tableAction',
        )));

        $this->add('generator_general', new Routing\Route('/generator-general/{id}/{namespace}', array(
            'id' => null,
            'namespace' => null,
            '_controller' => 'Generator\\Code\\Controllers\\GeneratorController::generalAction',
        )));

        $this->add('generator_fieldlist', new Routing\Route('/generator-fieldlist/{table}', array(
            'table' => null,
            '_controller' => 'Generator\\Code\\Controllers\\GeneratorController::fieldlistAction',
        )));

        $this->add('generator_upload', new Routing\Route('/generator-upload/{path}', array(
            'path' => null,
            '_controller' => 'Generator\\Code\\Controllers\\GeneratorController::uploadAction',
        )));

        $this->add('extension_upload', new Routing\Route('/extension-upload', array(
            '_controller' => 'Extensions\\Extensions\\Code\\Controllers\\ExtensionsController::extensionuploadAction',
        )));

        $this->add('extension_single', new Routing\Route('/extension-single', array(
            '_controller' => 'Extensions\\Extensions\\Code\\Controllers\\ExtensionsController::extensionsingleAction',
        )));

        $this->add('extension_list', new Routing\Route('/extension-list', array(
            '_controller' => 'Extensions\\Extensions\\Code\\Controllers\\ExtensionsController::extensionlistAction',
        )));

        $this->add('extension_download', new Routing\Route('/extension-download', array(
            '_controller' => 'Extensions\\Extensions\\Code\\Controllers\\ExtensionsController::extensiondownloadAction',
        )));

        /* xxxxxxxxxxxxxxxxxxxxxxxxxxx System Management xxxxxxxxxxxxxxxxxxxxxxxxxx */

        $this->add('system_install', new Routing\Route('/admin/system-install/{path}/{type}/{namespace}', array(
            'path' => null,
            'type' => null,
            'namespace' => null,
            '_controller' => 'System\\Extensions\\Code\\Controllers\\Admin\\ExtensionsController::installAction',
        )));

        $this->add('system_download', new Routing\Route('/admin/system-download/{name}/{extension}/{repository_id}', array(
            'name' => null,
            'extension' => null,
            'repository_id' => null,
            '_controller' => 'System\\Extensions\\Code\\Controllers\\Admin\\ExtensionsController::updatesystemAction',
        )));

        try {
            $this->addFromDatabase();
        } catch (\Exception $ex) {
            $this->prepareTables();
        }
    }

    public function prepareTables() {
        global $sc;

        $doctrine = $sc->get('doctrine');

        $doctrine->entity_path = JPATH_ROOT . 'applications/Setup/Countries/Code/Tables';
        $doctrine->getEntityManager();
        
        $doctrine->entity_path = JPATH_ROOT . 'applications/System/Flexviews/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/System/Flexviews/Positions/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/System/Routes/Flexviews/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/System/Crons/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/System/Pages/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/System/Listeners/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/System/Languages/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/System/Subsets/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/System/Routes/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/System/Templates/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/System/Routes/Permissions/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/Users/Groups/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/Users/Users/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/Users/Permission/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/Users/Roles/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/Users/Users/Groups/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/Users/Users/Roles/Code/Tables';
        $doctrine->getEntityManager();
                
        $doctrine->entity_path = JPATH_ROOT . 'applications/Users/Groups/Roles/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/System/Settings/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/System/Extensions/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/Search/Subsets/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/System/Listeners/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/Notification/Templates/Layouts/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/Notification/Subscribers/Harvesters/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/Notification/Newsletters/Automated/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/Notification/Newsletters/Scheduled/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/Notification/Newsletters/Groups/Code/Tables';
        $doctrine->getEntityManager();

        $doctrine->entity_path = JPATH_ROOT . 'applications/Notification/Subscribers/Harvesters/Code/Tables';
        $doctrine->getEntityManager();
        
        $doctrine->entity_path = JPATH_ROOT . 'applications/Notification/Subscribers/Groups/Code/Tables';
        $doctrine->getEntityManager();

        $this->addFromDatabase();
    }

    public function addFromDatabase() {
        // print_r($pathinfo); exit;
        $this->processPagesTable();
        $this->processRoutesTable();
    }

    public function processPagesTable() {

        $pages = $this->getPageRecords();

        if (!empty($pages)) {
            foreach ($pages as $key => $page) {

                $route_path = '/' . trim($page['route'], '/');

                $route_params = array(
                    '_controller' => 'System\\Pages\\Code\\Controllers\\PagesController::detailAction',
                );

                $this->add($page['unique_name'], new Routing\Route($route_path, $route_params));
            }
        }
    }

    public function processRoutesTable() {

        $string = new StringModification();
        $routes = $this->getRouteRecords();

        if (!empty($routes)) {
            foreach ($routes as $key => $route_single) {

                $seo_url_path = ($route_single['seo_url'] <> '') ? '/' . trim($route_single['seo_url'], '/') : '';
                $seo_arguments = json_decode($route_single['seo_arguments'], true);
                $route_path = '/' . trim($route_single['route'], '/');
                $arguments = json_decode($route_single['arguments'], true);
                $unique_name = str_replace('.', '_', $route_single['unique_name']);

                $route_params = array(
                    '_controller' => $route_single['controller'],
                );


                if ($seo_url_path <> '') {

                    if (!empty($seo_arguments)) {
                        foreach ($seo_arguments as $key => $seo_argument) {
                            $route_params[$key] = ($seo_argument == '') ? null : $seo_argument;
                        }
                    }

                    $this->add($route_single['unique_name'], new Routing\Route($seo_url_path, $route_params));
                } else {

                    if (!empty($arguments)) {
                        foreach ($arguments as $key => $argument) {
                            $route_params[$key] = ($argument == '') ? null : $argument;
                        }
                    }

                    $this->add($route_single['unique_name'], new Routing\Route($route_path, $route_params));
                }
            }
        }
    }

    public function getPageRecords() {

        $conn = $this->getConnection();

        $qb = $conn->createQueryBuilder();

        $qb->select('sp.*');
        $qb->from($this->prefix . 'system_pages', 'sp');

        $query = $qb->getSql();
        $pages = $conn->executeQuery($query)->fetchAll();

        return $pages;
    }

    public function getRouteRecords() {

        $conn = $this->getConnection();

        $qb = $conn->createQueryBuilder();

        $qb->select('r.*');
        $qb->from($this->prefix . 'system_routes', 'r');

        $query = $qb->getSql();
        $routes = $conn->executeQuery($query)->fetchAll();

        return $routes;
    }

    public function getConnection() {
        global $sc;

        $doctrine = $sc->get('doctrine');

        $this->prefix = $doctrine->prefix;
        $entityManager = $doctrine->getEntityManager();
        $conn = $entityManager->getConnection();

        return $conn;
    }

}
