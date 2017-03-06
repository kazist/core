<?php

namespace Kazist\Service\System;

defined('KAZIST') or exit('Not Kazist Framework');

use Kazist\KazistFactory;
use Kazist\Model\KazistModel;
use Kazist\Service\Database\Query;

/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
class System {

    public $container = '';
    public $request = '';

    public function __construct($container = '', $request = '') {
        $this->container = $container;
        $this->request = $request;
    }

    function callFunctionDynamically($class_name, $function_name, $args) {

        $factory = new KazistFactory();

        $args = (is_array($args)) ? $args : array();
        $class_name = str_replace('.', '\\', $class_name);

        if (class_exists($class_name)) {

            $class = new $class_name;

            if (method_exists($class, $function_name)) {

                $reflectionmethod = new \ReflectionMethod($class, $function_name);
                $parameters = $reflectionmethod->getParameters();



                switch (count($parameters)) {
                    case 1:
                        list($v1) = $this->callFunctionDynamicallyParameters($parameters, $args);
                        $data = $class->$function_name($v1);
                        break;

                    case 2:
                        list($v1, $v2) = $this->callFunctionDynamicallyParameters($parameters, $args);
                        $data = $class->$function_name($v1, $v2);
                        break;

                    case 3:
                        list($v1, $v2, $v3) = $this->callFunctionDynamicallyParameters($parameters, $args);
                        $data = $class->$function_name($v1, $v2, $v3);
                        break;
                    case 4:
                        list($v1, $v2, $v3, $v4) = $this->callFunctionDynamicallyParameters($parameters, $args);
                        $data = $class->$function_name($v1, $v2, $v3, $v4);
                        break;
                    case 5:
                        list($v1, $v2, $v3, $v4, $v5) = $this->callFunctionDynamicallyParameters($parameters, $args);
                        $data = $class->$function_name($v1, $v2, $v3, $v4, $v5);
                        break;
                    case 6:
                        list($v1, $v2, $v3, $v4, $v5, $v6) = $this->callFunctionDynamicallyParameters($parameters, $args);
                        $data = $class->$function_name($v1, $v2, $v3, $v4, $v5, $v6);
                        break;
                    case 7:
                        list($v1, $v2, $v3, $v4, $v5, $v6, $v7) = $this->callFunctionDynamicallyParameters($parameters, $args);
                        $data = $class->$function_name($v1, $v2, $v3, $v4, $v5, $v6, $v7);
                        break;
                    case 8:
                        list($v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8) = $this->callFunctionDynamicallyParameters($parameters, $args);
                        $data = $class->$function_name($v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8);
                        break;
                    case 9:
                        list($v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8, $v9) = $this->callFunctionDynamicallyParameters($parameters, $args);
                        $data = $class->$function_name($v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8, $v9);
                        break;
                    case 10:
                        list($v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8, $v9, $v10) = $this->callFunctionDynamicallyParameters($parameters, $args);
                        $data = $class->$function_name($v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8, $v9, $v10);
                        break;
                    default:
                        $data = $class->$function_name();
                        break;
                }
            } else {
                $data = $msg = 'Function ' . $function_name . ' Not found in class ' . $class_name;
            }
        } else {
            $data = $msg = 'Class ' . $class_name . ' Not found ';
        }

        return $data;
    }

    function callCurl($url) {

        $curl = curl_init();

        $post['test'] = 'test';
        
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);

        curl_setopt($curl, CURLOPT_USERAGENT, 'croncall');
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, 500);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 10);

        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);

        $data = curl_exec($curl);
        
        curl_close($curl);
    }

    function callCurlByUniqueName($unique_name) {

        $kazist_model = new KazistModel();

        $url = $kazist_model->generateUrl($unique_name, null, 0);

        $this->callCurl($url);
    }

    function callFunctionDynamicallyParameters($parameters, $args) {

        $arguments = array();

        foreach ($parameters as $key => $arg) {

            if (isset($args[$arg->name])) {
                $arguments[] = $args[$arg->name];
            } else if ($arg->isDefaultValueAvailable()) {
                $arguments[] = $arg->getDefaultValue();
            } else {
                $arguments[] = null;
            }
        }

        return $arguments;
    }

    function processImagesUrl($response_content) {

        // print_r($response_content); exit;
        $response_content = str_replace('"../uploads', '"' . WEB_ROOT . 'uploads', $response_content);
        $response_content = str_replace('"/uploads', '"' . WEB_ROOT . 'uploads', $response_content);
        $response_content = str_replace('"uploads/', '"' . WEB_ROOT . 'uploads/', $response_content);
        $response_content = str_replace('&quot;../uploads', '"' . WEB_ROOT . 'uploads', $response_content);
        $response_content = str_replace('&quot;/uploads', '"' . WEB_ROOT . 'uploads', $response_content);
        $response_content = str_replace('&quot;uploads/', '"' . WEB_ROOT . 'uploads/', $response_content);
        //  print_r($response_content); exit;

        return $response_content;
    }

    function getSubsetId($path) {

        $query = new Query();
        $query->select('id');
        $query->from('#__system_subsets');
        $query->where('path=:path');
        $query->setParameter('path', $path);
        $record = $query->loadObject();

        return $record->id;
    }

    function getSubsetById($id) {

        $query = new Query();
        $query->select('*');
        $query->from('#__system_subsets');
        $query->where('id=:id');
        $query->setParameter('id', $id);
        $record = $query->loadObject();

        return $record;
    }

}
