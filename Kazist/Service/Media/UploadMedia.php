<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Kazist\Service\Media;

defined('KAZIST') or exit('Not Kazist Framework');

use Kazist\KazistFactory;
use Joomla\Input\Files;
use Kazist\Service\System\System;
use Kazist\Service\Media\MediaManager;

/**
 * Description of Upload
 *
 * @author sbc
 */
class UploadMedia {

    private $application_name = '';
    private $component_name = '';
    private $subset_name = '';

    public function uploadFile($name, $path = '', $addition_path = '', $route = '') {

        $media_ids = array();
        $factory = new KazistFactory();

        $targeted_files = $this->getTargetedFiles($name);

        if (is_array($targeted_files)) {

            $uploaddir = $this->getUploadDir($path, $addition_path, $route);

            if (isset($targeted_files['name'])) {
                $media_ids[] = $this->updateMedia($uploaddir, $targeted_files);
            } else {
                foreach ($targeted_files as $key => $targeted_file) {

                    if (isset($targeted_file['name']) && $targeted_file['name'] != '') {
                        $media_ids[$key] = $this->updateMedia($uploaddir, $targeted_file);
                    }
                }
            }

            return $media_ids;
        } else {
            return false;
        }
    }

    function getTargetedFiles($name) {

        $file = new Files;

        $uploads = $file->get('form');

        $name_arr = explode('.', $name);
        $name_0 = (isset($name_arr[0])) ? $name_arr[0] : '';

        if (!isset($uploads[$name_0])) {
            $uploads = $file->get($name_0);
        }

        foreach ($name_arr as $name) {
            if (isset($uploads[$name])) {
                $uploads = $uploads[$name];
            }
        }

        return $uploads;
    }

    function saveFileToMedia($file_path, $app, $com, $subset) {

        $this->application_name = $app;
        $this->component_name = $com;
        $this->subset_name = $subset;

        $data_object = $this->prepareDataForSaving($file_path);
        $media_id = $this->updateDB($data_object);

        return $media_id;
    }

    //xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx Update Media
    function updateMedia($uploaddir, $targeted_file) {

        if ($targeted_file['name'] != '') {

            $data_object = $this->prepareDataForSaving($uploaddir . $targeted_file['name']);

            $this->moveUploadedFile($uploaddir, $targeted_file);

            $media_id = $this->updateDB($data_object);

            return $media_id;
        } else {
            return false;
        }
    }

    function prepareDataForSaving($file_path) {

        $mediamanager = new MediaManager();
        $system = new System();
        $tmpobject = new \stdClass();

        $tmpobject->application_id = $system->getApplicationIdByName($this->application_name);
        $tmpobject->component_id = $system->getComponentIdByName($this->component_name, $tmpobject->application_id);
        $tmpobject->subset_id = $system->getSubsetIdByName($this->subset_name, $tmpobject->component_id, $tmpobject->application_id);

        $tmpobject->file = $file_path;
        $tmpobject->extension = $mediamanager->getFileExtension($file_path);
        $tmpobject->type = $mediamanager->getFileType($tmpobject->extension);
        $tmpobject->not_found = 0;


        return $tmpobject;
    }

    function updateDB($data_object) {

        $factory = new KazistFactory;
        $db = $factory->getDatabase();

        $media_id = $this->fileExist($data_object->file);

        if ($media_id) {
            $data_object->id = $media_id;
        }

        $factory->saveRecord('#__media_media', $data_object);

        return $media_id;
    }

    function fileExist($file_location) {

        $query = new Query();

        $query->select('mm.*');
        $query->from('#__media_media', 'mm');
        $query->where('mm.file=:file');
        $query->setParameter('file', $file_location);

        $record = $query->loadObject();

        return (is_object($record)) ? $record->id : false;
    }

    function moveUploadedFile($uploaddir, $uploads) {

        if (move_uploaded_file($uploads['tmp_name'], JPATH_ROOT . '/' . $uploaddir . basename($uploads['name']))) {
            $files[] = $uploaddir . $uploads['name'];
        } else {
            $error = true;
        }
    }

    function getUploadDir($path, $addition_path = '', $app = '', $com = '', $subset = '') {

        $factory = new KazistFactory();
        $input = $factory->getInput();

        if ($path == '') {

            $uploaddir = 'uploads/';
            $this->application_name = ($app <> '') ? $app : $this->request->get('app');
            $this->component_name = ($com <> '') ? $com : $this->request->get('com');
            $this->subset_name = ($subset <> '') ? $subset : $this->request->get('subset', $this->component_name);

            if ($this->application_name <> '') {
                $uploaddir = $uploaddir . $this->application_name . '/';
            }

            if ($this->component_name <> '') {
                $uploaddir = $uploaddir . $this->component_name . '/';
            }

            if ($this->subset_name <> '') {
                $uploaddir = $uploaddir . $this->subset_name . '/';
            }

            if ($addition_path <> '') {
                $uploaddir = $uploaddir . $addition_path . '/';
            }
        } else {
            $uploaddir = $path . '/' . $addition_path;
        }

        $factory->makeDir(JPATH_ROOT . '/' . $uploaddir);

        return $uploaddir;
    }

    public function downloadImageNSave($image_url, $image_name, $path = '', $addition_path = '', $app = '', $com = '', $subset = '') {

        $mediamanager = new MediaManager();
        $extension = $mediamanager->getFileExtension($image_url);

        $uploaddir = $this->getUploadDir($path, $addition_path, $app, $com, $subset);

        if ($image_name == '') {
            $image_name = uniqid() . '.' . $extension;
        }

        $ch = curl_init($image_url);
        $fp = fopen($uploaddir . $image_name, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $result = curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        return $this->saveFileToMedia($uploaddir . $userImage, $app, $com, $subset);
    }

}
