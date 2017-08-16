<?php

/*
 * To change this l$class_objicense header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Kazist\Service\System;

defined('KAZIST') or exit('Not Kazist Framework');

use Kazist\KazistFactory;
use Cron\CronExpression;
use Kazist\Service\Database\Query;
use Kazist\Service\ContainerAwareControllerResolver;
use Kazist\Model\BaseModel;
use Kazist\Service\System\System;

class Cron {

    public $container = '';
    public $request = '';

    public function __construct($container, $request = '') {
        $this->container = $container;
        $this->request = $request;
    }

    public function processCron() {

        ini_set('max_execution_time', 7200);

        $factory = new KazistFactory();

        $this->processFileCrontoDatabase();
        $this->deleteEmptyCrons();

        $crons = $this->getCronList();

        $this->printSeparator();

        if (!empty($crons)) {
            foreach ($crons as $key => $cron) {
                $cron_str = $this->getCronStr($cron);
                $this->updateNextRunTime($cron->id, $cron_str);
            }
        }

        if (!empty($crons)) {
            foreach ($crons as $key => $cron) {

                $this->printSeparator();
                $this->callCronByCurl($cron);
                $this->printSeparator();
            }
        }

        $this->printSeparator();
    }

    public function callCronByCurl($cron) {

        $factory = new KazistFactory();

        $http_host = $this->request->server->get('HTTP_HOST');
        $request_uri = $this->request->server->get('REQUEST_URI');
        $unique_name = $cron->unique_name;

        $this->printSeparator();
        $this->printLog($unique_name);

        $url = $http_host . $factory->generateUrl($unique_name);
        $url = str_replace('cron.', 'index.', $url);
        $url = str_replace('cron-dev.', 'index-dev.', $url);

        $curl = curl_init();

        $post['test'] = 'test';

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);

        curl_setopt($curl, CURLOPT_USERAGENT, 'croncall');
        curl_setopt($curl, CURLOPT_HEADER, 0);

        $data = curl_exec($curl);

        curl_close($curl);

        $this->printSeparator();
    }

    private function callFunction($cron) {

        error_reporting(E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_ERROR | E_CORE_ERROR);

        $container_aware = new ContainerAwareControllerResolver();
        $class_name = $cron->controller;
        $function_name = $cron->function;

        if (class_exists($class_name)) {
            $this->printLog($class_name . ' Class Exist.');
            $class_obj = new $class_name;
            if (method_exists($class_obj, $function_name)) {

                $model = $this->getModelClass($class_name);

                $class_obj->setContainer($this->container);
                $class_obj->setRequest($this->request);
                $class_obj->setDoctrine();
                $class_obj->model = $model;

                $class_obj->$function_name();

                $this->printLog($function_name . ' Function Exist.');
            } else {
                $this->printLog($function_name . ' Function Does not Exist.', true);
            }
        } else {
            $this->printLog($class_name . ' Class Does not Exist.', true);
        }
    }

    public function updateCompleted($id) {

        $factory = new KazistFactory();

        $stdclass = new \stdClass();
        $stdclass->id = $id;
        $stdclass->completed_running = 1;
        $factory->saveRecord('#__system_crons', $stdclass);
    }

    public function updateNextRunTime($id, $cron_str) {

        $factory = new KazistFactory();

        $cron = CronExpression::factory($cron_str);
        $next_run_time = $cron->getNextRunDate()->format('Y-m-d H:i:s');

        $stdclass = new \stdClass();
        $stdclass->id = $id;
        $stdclass->is_new = 0;
        $stdclass->completed_running = 0;
        $stdclass->next_run_time = $next_run_time;

        $factory->saveRecord('#__system_crons', $stdclass);
    }

    public function getCronStr($cron) {

        $cron_str = $cron->minute . ' '
                . $cron->hour . ' '
                . $cron->day_of_month . ' '
                . $cron->month . ' '
                . $cron->day_of_week . ' '
                . $cron->year . ' '
        ;

        return $cron_str;
    }

    public function getCronUniqueName($unique_name) {

        $query = new Query();

        $query->select('*');
        $query->from('#__system_crons');
        $query->where('unique_name=:unique_name');
        $query->setParameter('unique_name', $unique_name);

        $cron = $query->loadObject();

        return $cron;
    }

    public function processFileCrontoDatabase() {

        $cron_list = array();

        $factory = new KazistFactory();

        //Fetch Cron from folder list
        $dir = new \DirectoryIterator(JPATH_ROOT . '/applications');

        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {

                $folder_path = JPATH_ROOT . '/applications/' . ucfirst($fileinfo->getFilename());

                if (file_exists($folder_path . '/cron.json')) {

                    $crons = json_decode(file_get_contents($folder_path . '/cron.json'));

                    foreach ($crons as $cron) {
                        $cron_list[$cron->unique_name] = $cron;
                    }
                }
            }
        }

        //Fetch Cron from database
        $query = new Query();
        $query->select('*');
        $query->from('#__system_crons');
        $db_crons = $query->loadObjectList();

        foreach ($db_crons as $db_cron) {
            if (array_key_exists($db_cron->unique_name, $cron_list)) {
                unset($cron_list[$db_cron->unique_name]);
            }
        }

        //Add Cron files to database
        foreach ($cron_list as $cron) {

            $class_arr = explode('Code', $cron->controller);

            $cron->locked_key = '';
            $cron->extension_path = rtrim($class_arr[0], '\\');

            $factory->saveRecord('#__system_crons', $cron);
        }
    }

    public function getCronList() {

        $random_number = uniqid();

        $query = new Query();
        $query->select('*');
        $query->from('#__system_crons');
        $query->where('published=1');
        $query->andWhere('next_run_time IS NULL OR next_run_time = \'\' OR next_run_time<\'' . date('Y-m-d H:i:s') . '\' OR '
                . 'unique_name = \'notification.emails.cronemailsender\'');
        $query->setFirstResult(0);
        $query->setMaxResults(3);

        $crons = $query->loadObjectList();


        return $crons;
    }

    private function printSeparator() {
        echo '********************************************************';
        echo '<br>';
    }

    private function printLog($string, $is_danger = false) {
        if ($is_danger) {
            echo "<span style=\"color:red\">" . $string . "</span>";
        } else {
            echo $string;
        }
        echo '<br>';
    }

    private function deleteEmptyCrons() {

        $factory = new KazistFactory();
        $factory->deleteRecords('#__system_crons', array('controller = \'\'', 'controller IS NULL'));
    }

    public function getModelClass($controller) {

        $class_arr = array();

        $controller_arr = explode('Code', $controller);

        if (count($controller_arr) > 1) {
            $class_arr = $initial_arr = array_filter(explode('\\', $controller_arr[0]));
            $class_arr[] = 'Code';
            $class_arr[] = 'Models';
            $class_arr[] = end($initial_arr) . 'Model';
        }

        $class_name = implode('\\', $class_arr);

        if (class_exists($class_name)) {
            $model = new $class_name($this->container->get('doctrine'), $this->request);
        } else {
            $model = new BaseModel($this->container->get('doctrine'), $this->request);
        }

        return $model;
    }

}
