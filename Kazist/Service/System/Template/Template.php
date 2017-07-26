<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Kazist\Service\System\Template;

/**
 * Description of Template
 *
 * @author sbc
 */
use Kazist\Service\Database\Query;
use Kazist\KazistFactory;
use Kazist\Model\KazistModel;

class Template {

    public $container = '';
    public $request = '';

//put your code here
    function __construct($container, $request) {
        $this->container = $container;
        $this->request = $request;
    }

    public function applyTemplateToResponse($response_content, $status_code) {

        $data = array();
        $tmp_data = array();

        $kazistmodel = new KazistModel();

        $twig = $this->container->get('twig');
        $session = $this->container->get('session');
        $document = $this->container->get('document');

        $token = $session->get('security.token');
        $template_appended = $session->get('template.appended');


        if ($template_appended) {
            // return $response_content;
        }

        $user = $kazistmodel->getUser();


        $data['document'] = $document;
        $data['user'] = $user;
        $data['response_content'] = $response_content;

        $twig_file = (WEB_IS_ADMIN && (!is_object($user) || !$user->id)) ? 'login.twig' : 'index.twig';
        $positions = $this->getPositions($data, $twig_file);

        foreach ($positions as $position) {
            $data[$position] = $this->getFlexviewHtml($position);
        }

        $content = $twig->render($twig_file, $data);

        $session->set('template.appended', true);

        // 

        return $content;
    }

    public function getFlexviewHtml($position) {

        $where_arr = array();
        $tmp_array = array();

        $query = new Query();
        $kazimodel = new KazistModel();

        $document = $this->container->get('document');

        $viewside = (WEB_IS_ADMIN) ? 'backend' : 'frontend';

        $query->select('sf.*');
        $query->from('#__system_flexviews', 'sf');
        $query->leftJoin('sf', '#__system_flexviews_positions', 'sfp', 'sfp.flexview_id=sf.id');
        $query->where('sfp.position=:position');
        $query->andWhere('sf.viewside=:viewside');
        $query->andWhere('sf.published=1');
        $query->setParameter('position', $position);
        $query->setParameter('viewside', $viewside);

        if (WEB_IS_HOMEPAGE) {
            $where_arr[] = 'sf.homepage=1';
        } else {
            $where_arr[] = 'sf.allpages=1';
        }

        if (!WEB_IS_HOMEPAGE) {
            if ((int) $document->id) {
                $where_arr[] = '(sf.extensionpages=1 AND sf.extension_id=' . (int) $document->subset->extension_id . ')';
            } else {
                $where_arr[] = '(sf.extensionpages=1 AND 1=-1)';
            }
        }

        $visible_ids = $this->getFlexviewByPattern(true);
        $hidden_ids = $this->getFlexviewByPattern(false);

        if (!empty($visible_ids)) {
            $where_arr[] = 'sf.id IN (' . implode(',', $visible_ids) . ')';
        }

        if (!empty($hidden_ids)) {
            $query->andWhere('sf.id NOT IN (' . implode(',', $hidden_ids) . ')');
        }

        $query->andWhere(implode(' OR ', $where_arr));

        $query->orderBy('-sf.ordering', 'DESC');

        $records = $query->loadObjectList();

        foreach ($records as $key => $record) {

            $unique_name = $record->unique_name;
            $render = $record->render;
            $params = json_decode($record->params, true);


            if ($render == 'flexview') {

                $response = $this->getControllerResponse($unique_name, $params);

                if (is_object($response) && !ctype_space($response->getContent())) {
                    $record->html = $response->getContent();
                    $tmp_array[$record->id] = $record;
                }
            } elseif ($render == 'custom') {
                $record->html = $kazimodel->renderString($record->main_content, array());
                $tmp_array[$record->id] = $record;
            } elseif ($render == 'twig') {
                $record->html = $kazimodel->renderData($record->twig_file, array());
                $tmp_array[$record->id] = $record;
            }
        }

        return $tmp_array;
    }

    public function preparePatterns() {

        $route_arr = array();

        $request = $this->container->get('request');
        $path = trim($request->getPathInfo(), '/');

        $path_arr = explode('/', $path);

        $route_arr[] = '*';
        $route_arr[] = $path;

        foreach ($path_arr as $key => $path_item_1) {

            $is_found = false;
            $tmp_arr_1 = array();
            $tmp_arr_2 = array();
            $tmp_arr_3 = array();

            foreach ($path_arr as $key => $path_item_2) {

                $tmp_arr_1[] = ($path_item_1 == $path_item_2) ? '*' : $path_item_2;
                $tmp_arr_2[] = ($path_item_1 == $path_item_2) ? $path_item_2 : '*';
                if ($path_item_1 == $path_item_2 && !$is_found) {
                    $tmp_arr_3[] = $path_item_2;
                    $tmp_arr_3[] = '*';
                    $is_found = true;
                } elseif (!$is_found) {
                    $tmp_arr_3[] = $path_item_2;
                }
            }

            $route_arr[] = implode('/', $tmp_arr_1);
            $route_arr[] = implode('/', $tmp_arr_2);
            $route_arr[] = implode('/', $tmp_arr_3);
            //  $route_arr[] = implode('/', $tmp_arr_4);
        }

        return $route_arr;
    }

    public function getFlexviewByPattern($visible = true) {

        $tmp_array = array();
        $query = new Query();

        $patterns = $this->preparePatterns();

        $query->select('srf.flexview_id');
        $query->from('#__system_routes_flexviews', 'srf');
        $query->where('srf.route=\'' . implode('\' OR srf.route=\'', $patterns) . '\'');

        if ($visible) {
            $query->andWhere('srf.visible=1');
        } else {
            $query->andWhere('srf.hidden=1');
        }

        $records = $query->fetchAll();

        foreach ($records as $record) {
            $tmp_array[] = $record['flexview_id'];
        }

        return $tmp_array;
    }

    public function getControllerResponse($unique_name, $params) {
        // error_reporting(E_ALL);
        $kazimodel = new KazistModel();
        $factory = new KazistFactory();

        $params = (is_array($params)) ? $params : array();

        $kazimodel->container = $this->container;
        $kazimodel->request = $this->request;
        $kazimodel->doctrine = $this->container->get('doctrine');

        $route = $this->container->getParameter('routes')->get($unique_name);
        $defaults = $route->getDefaults();
        $controller_class = $defaults['_controller'];

        if ($controller_class != '') {

            $controller_class_arr = explode('::', $controller_class);
            $class_name = $controller_class_arr[0];
            $class_function = $controller_class_arr[1];

            if (class_exists($class_name)) {
                $class_object = new $class_name();

                if (method_exists($class_object, $class_function)) {
                    $controller_path_arr = explode('Controllers', $controller_class);
                    $twig_paths[] = JPATH_ROOT . 'applications/' . str_replace('\\', '/', $controller_path_arr[0]) . 'views';

                    try {

                        $response = $kazimodel->getControllerResponse($class_name, $class_function, $params, $twig_paths);

                        return (is_object($response) && $response->getStatusCode() == 200) ? $response : false;
                    } catch (\Exception $ex) {
                        $factory->loggingException($ex);
                        throw $ex;
                    }
                } else {
                    $factory->enqueueMessage('Function Not Found ' . $class_function . ' in Class ' . $class_name);
                }
            } else {
                $factory->enqueueMessage('Class Not Found ' . $class_name);
            }
        } else {
            $factory->enqueueMessage('controller route not found ' . $unique_name);
            return false;
        }
    }

    public function getPositions($data, $twig_file) {

        $jpath_templates = str_replace('/views', '', JPATH_TEMPLATES);
        $type = str_replace('.twig', '', $twig_file);

        $position_json = file_get_contents($jpath_templates . '/positions.json');

        $position_arr = json_decode($position_json, true);

        return (isset($position_arr[$type])) ? $position_arr[$type] : array();
    }

}
