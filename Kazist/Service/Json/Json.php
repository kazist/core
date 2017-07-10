<?php

namespace Kazist\Service\Json;

defined('KAZIST') or exit(DIE_MSG);

use Kazist\KazistFactory;
use Kazist\Service\Database\Table;
use Kazist\Service\System\System;
use Kazist\Service\Database\Query;

class Json {

    public function getJsonByTableName($table_name) {
        // print_r($table_name); exit;
        $subset = $this->getSubsetByTableName($table_name);

        if (!is_object($subset)) {
            throw new \Exception('Get Json By Table Name Failed. Subbset does not exist ' . $table_name);
        }

        $app_name = strtolower($subset->application_name);
        $com_name = strtolower($subset->component_name);
        $subset_name = strtolower($subset->name);


        $structure_object = $this->getJson($app_name, $com_name, $subset_name);


        return $structure_object;
    }

    public function getJson($app_name = '', $com_name = '', $subset_name = '') {
        $json = new Json;
        $table = new Table;
        $system = new System;
        $factory = new KazistFactory;

        $session = $factory->getSession();


        $json_cache = $session->get('json_cache');
        $json_cache_time = $session->get('json_cache_time');

        $structure_object = new \stdClass;

        $structure_file = $this->getJsonFile($app_name, $com_name, $subset_name);

        if ($json_cache_time[$structure_file] < time()) {

            if (file_exists($structure_file)) {
                $structure_object = $this->configObject($structure_file);
            } else {
                throw new \Exception('Json File does not exist.' . $app_name . ', com=' . $com_name . ', subset=' . $subset_name . ' ' . $structure_file);
            }



            if (isset($structure_object->fields) && count($structure_object->fields)) {
                foreach ($structure_object->fields as $key => $field) {
                    if (!isset($field->name)) {
                        continue;
                    }

                    if (!isset($field->view_side) || $field->view_side == '') {
                        $structure_object->fields[$key]->view_side = 'both';
                    }

                    $structure_object->fields[$field->name] = $structure_object->fields[$key];

                    unset($structure_object->fields[$key]);
                }

                if (isset($structure_object->views)) {
                    $structure_object->views = (array) $structure_object->views;
                }

                $table_name = $table->getTableName($app_name, $com_name, $subset_name);
                $subset = $this->getSubsetByTableName($table_name);

                $structure_object->table_alias = $subset->table_alias;

                if (!isset($structure_object->table_name)) {
                    $structure_object->table_name = $subset->table_name;
                }
            }

            $structure_object->app_name = $app_name;
            $structure_object->com_name = $com_name;
            $structure_object->subset_name = $subset_name;

            $structure_object->email_on_new = (isset($structure_object->email_on_new)) ? $structure_object->email_on_new : '';
            $structure_object->email_on_edit = (isset($structure_object->email_on_edit)) ? $structure_object->email_on_edit : '';
            $structure_object->email_on_delete = (isset($structure_object->email_on_delete)) ? $structure_object->email_on_delete : '';

            $structure_object->app_id = $system->getApplicationIdByName($app_name);
            $structure_object->com_id = $system->getComponentIdByName($com_name, $structure_object->app_id);
            $structure_object->subset_id = $system->getSubsetIdByName($subset_name, $structure_object->com_id);
            $structure_object->subset = $system->getSubsetByName($subset_name, $structure_object->com_id);

            $json_cache_time[$structure_file] = strtotime('+1 hour');
            $json_cache[$structure_file] = $structure_object;
        } else {
            $structure_object = $json_cache[$structure_file];
        }

        //print_r($structure_object); exit;

        return $structure_object;
    }

    public function getJsonLite($app_name = '', $com_name = '', $subset_name = '') {

        $factory = new KazistFactory;

        $session = $factory->getSession();

        $jsonlite_cache = $session->get('jsonlite_cache');
        $jsonlite_cache_time = $session->get('jsonlite_cache_time');

        $app_name = ($app_name <> '') ? $app_name : $this->request->request->get('app');
        $com_name = ($com_name <> '') ? $com_name : $this->request->request->get('com');
        $subset_name = ($subset_name <> '') ? $subset_name : $this->request->request->get('subset');

        $subset_name = ($subset_name <> '') ? $subset_name : $com_name;

        $structure_file = $this->getJsonFile($app_name, $com_name, $subset_name);

        if ($jsonlite_cache_time[$structure_file] < time()) {
            if (file_exists($structure_file)) {
                $structure_object = $this->configObject($structure_file);
            } else {
                throw new \Exception('Json File does not exist.' . $app_name . ', com=' . $com_name . ', subset=' . $subset_name . ' ' . $structure_file);
            }

            $jsonlite_cache_time[$structure_file] = strtotime('+1 hour');
            $jsonlite_cache[$structure_file] = $structure_object;
        } else {
            $structure_object = $jsonlite_cache[$structure_file];
        }

        return $structure_object;
    }

    public function getJsonFile($app_name = '', $com_name = '', $subset_name = '') {

        $factory = new KazistFactory;

        $session = $factory->getSession();

        $kazi_url = $session->get('kazi_url');

        $app_name = ($app_name <> '') ? $app_name : $this->request->request->get('app');
        $com_name = ($com_name <> '') ? $com_name : $this->request->request->get('com');
        $subset_name = ($subset_name <> '') ? $subset_name : $this->request->request->get('subset');

        $subset_name = ($subset_name <> '') ? $subset_name : $com_name;

        $structure_file = JPATH_ROOT . '/applications/' . ucfirst($app_name) . '/Components/' . ucfirst($com_name) . '/properties/' . $subset_name . '/structure.json';

        return $structure_file;
    }

    public function configObject($file) {

        $factory = new KazistFactory;

        $session = $factory->getSession();

        $structure_files = ($session->has('structure_files')) ? $session->get('structure_files') : array();

        // Verify the configuration exists and is readable.
        if (!is_readable($file) || !file_exists($file)) {
            throw new \RuntimeException('file does not exist or is unreadable.' . $file);
        }

        if (file_exists($file)) {
            if (!array_key_exists($file, $structure_files)) {
                $file_content = file_get_contents($file);
                $configObject = json_decode($file_content, true);
                $structure_files[$file] = $configObject;
                $session->set('structure_files', $structure_files);
            } else {
                return $structure_files[$file];
            }
        } else {
            $configObject = null;
        }

        // Load the configuration file into an object.
        // print_r($file_content); exit;
        if ($configObject === null) {
            throw new \RuntimeException(sprintf('Unable to parse the view.' . $this->subset_name . '.json file %s.', $file));
        }

        return $configObject;
    }

    function getSubsetByTableName($table_name) {

        $query = new Query();

        $query->select('s.*, c.name as component_name, a.name as application_name');
        $query->where('s.table_name=:table_name');
        $query->andWhere('s.component_id <> :component_id');
        $query->andWhere('c.application_id <> :application_id');
        $query->setParameter('table_name', $table_name);
        $query->setParameter('application_id', '');
        $query->setParameter('component_id', '');
        $query->from('#__system_subsets', 's');
        $query->leftJoin('s', '#__system_components', 'c', ' ON c.id=s.component_id');
        $query->leftJoin('s', '#__system_applications', 'a', ' ON a.id=c.application_id');

        $record = $query->loadObject();

        return $record;
    }

}
