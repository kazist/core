<?php

/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Kazist;

defined('KAZIST') or exit('Not Kazist Framework');

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Kazist\Service\Database\Query;
use Kazist\Model\BaseModel;
use Kazist\Service\Media\MediaManager;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Database service provider
 *
 * @since  1.0
 */
class KazistFactory {

    private $container = '';
    public $ordering = 'DESC';

    public function __construct() {
        global $sc;

        $this->container = $sc;
    }

    public function isAdmin() {

        $baseModel = new BaseModel();
        return $baseModel->isAdmin();
    }

    public function getUser() {

        $baseModel = new BaseModel();
        return $baseModel->getUser();
    }

    public function getDbo() {
        return $this->getDatabase();
    }

    public function enqueueMessage($msg, $type = 'info') {
        $baseModel = new BaseModel();
        return $baseModel->enqueueMessage($msg, $type);
    }

    public function getSession() {
        return $this->container->get('session');
    }

    public function getDatabase() {

        $entityManager = $this->container->get('doctrine')->getEntityManager();

        $db = $entityManager->getConnection();

        return $db;
    }

    public function saveRecordByEntity($table_name, $data, $where_arr = array(), $parameter_arr = array()) {

        $baseModel = new BaseModel();
        return $baseModel->saveRecordByEntity($table_name, $data, $where_arr, $parameter_arr);
    }

    public function saveRecord($table_name, $data, $where_arr = array(), $parameter_arr = array()) {

        $baseModel = new BaseModel();
        return $baseModel->saveRecord($table_name, $data, $where_arr, $parameter_arr);
    }

    public function updateRecord($table_name, $data_arr, $where_arr = array(), $parameter_arr = array()) {
        $baseModel = new BaseModel();
        return $baseModel->updateRecord($table_name, $data_arr, $where_arr, $parameter_arr);
    }

    public function insertRecord($table_name, $data_arr) {
        $baseModel = new BaseModel();
        return $baseModel->insertRecord($table_name, $data_arr);
    }

    public function deleteRecords($table_name, $where_arr = array(), $parameter_arr = array()) {
        $baseModel = new BaseModel();
        return $baseModel->deleteRecords($table_name, $where_arr, $parameter_arr);
    }

    public function saveEntity($entity, $data) {
        $baseModel = new BaseModel();
        $baseModel->saveEntity($entity, $data);
    }

    public function bindDataToEntity($entity, $data) {
        $baseModel = new BaseModel();
        return $baseModel->bindDataToEntity($entity, $data);
    }

    public function getRecord($table_name, $table_alias, $where_arr = array(), $parmeter_arr = array(), $ordering_arr = array()) {
        try {
            $baseModel = new BaseModel();
            return $baseModel->getQueryedRecord($table_name, $table_alias, $where_arr, $parmeter_arr, $ordering_arr);
        } catch (Exception $ex) {
            throw new $ex;
        }
    }

    public function getRecords($table_name, $table_alias, $where_arr = array(), $parmeter_arr = array(), $ordering_arr = array(), $offset = '', $limit = '') {

        $baseModel = new BaseModel();
        return $baseModel->getQueryedRecords($table_name, $table_alias, $where_arr, $parmeter_arr, $ordering_arr, $offset, $limit);
    }

    public function getQueryBuilder($table_name, $table_alias, $where_arr = array(), $parmeter_arr = array(), $ordering_arr = array(), $offset = 0, $limit = 10) {

        $baseModel = new BaseModel();

        $baseModel->ordering = $this->ordering;

        return $baseModel->getQueryBuilder($table_name, $table_alias, $where_arr, $parmeter_arr, $ordering_arr, $offset, $limit);
    }

    public function getJson($table_name) {

        $baseModel = new BaseModel();

        return $baseModel->getJson($table_name);
    }

    public function renderString($html, $object_arr) {
        $baseModel = new BaseModel();
        return $baseModel->renderString($html, $object_arr);
    }

    public function renderData($template, $object_arr, $paths = array()) {
        $baseModel = new BaseModel();
        return $baseModel->renderData($template, $object_arr, $paths);
    }

    public function makeDir($dir) {

        if (!file_exists(rtrim($dir, '/'))) {
            $oldmask = umask(0);
            mkdir($dir, 0775, true);
            umask($oldmask);
        }

        $root_path = JPATH_ROOT;
        $new_dir = str_replace($root_path, '', $dir);
        $new_dir_arr = explode('/', $new_dir);

        $path_sum = $root_path;

        foreach ($new_dir_arr as $new_dir_item) {

            $path_sum = rtrim($path_sum, '/') . '/' . $new_dir_item;
            if ($path_sum !== $root_path) {
                if (!file_exists($path_sum . '/index.html')) {
                    file_put_contents($path_sum . '/index.html', '<html></html>');
                }
            }
        }
    }

    public function getTableAlias($table_name = '') {

        $table_alias_arr = array();
        $table_name_arr = explode('_', str_replace('#__', '', $table_name));

        if (!empty($table_name_arr)) {
            foreach ($table_name_arr as $key => $term) {
                $table_alias_arr[] = substr($term, 0, 1);
            }
        }

        $this->table_alias = implode('', $table_alias_arr);

        return $this->table_alias;
    }

    public function getRequest() {

        $request = $this->container->get('request');

        return $request;
    }

    public function getSetting($setting_name, $default = '') {


        $query = new Query();

        $query->select('ss.*');
        $query->from('#__system_settings', 'ss');
        $query->where('ss.name=:name');
        $query->setParameter('name', $setting_name);
        $record = $query->loadObject();

        return (is_object($record)) ? $record->value : $default;
    }

    public function getPhrase($phrase_name) {

        $db = $this->getDbo();

        $query = $db->getQuery(true);
        $query->select('sl.*');
        $query->from('#__system_languages', 'sl');
        $query->where('sl.name=name');
        $query->setParameter('name', $phrase_name);

        $record = $db->setQuery($query)->loadObject();

        if (is_object($record)) {
            return $record->value;
        } else {
            return $phrase_name;
        }
    }

    public function getMedia($media_id) {

        $query = $this->getQueryBuilder('#__media_media', 'mm');
        $query->where('mm.id=:id');
        $query->setParameter('id', $media_id);

        $record = $query->loadObject();

        return $record;
    }

    public function uploadMedia($fields, $extension, $alias) {

        global $sc;
        $request = $sc->get('request');
        $media_manager = new MediaManager();

        $media_ids = array();
        $uploaded_file = $request->files;
        $fields_arr = explode('.', $fields);

        $uploaddir = 'uploads/' . str_replace('.', '/', $extension . '.' . $alias);
        $upload_path = JPATH_ROOT . '/' . $uploaddir;

        foreach ($fields_arr as $field_item) {

            if (is_object($uploaded_file)) {
                $uploaded_file = $uploaded_file->get('form');
            } elseif (is_array($uploaded_file)) {
                $uploaded_file = $uploaded_file[$field_item];
            } else {
                $uploaded_file = '';
            }
        }

        $uploaded_files = (is_array($uploaded_file)) ? $uploaded_file : array($uploaded_file);

        foreach ($uploaded_files as $tmp_uploaded_file) {
            if (is_object($tmp_uploaded_file)) {

                $original_name = preg_replace("/[^A-Za-z0-9.]/", '-', $tmp_uploaded_file->getClientOriginalName());
                $web_file = $uploaddir . '/' . $original_name;

                $upload_detail['name'] = $tmp_uploaded_file->getClientOriginalName();
                $upload_detail['title'] = $tmp_uploaded_file->getClientOriginalName();
                $upload_detail['description'] = $tmp_uploaded_file->getClientOriginalName();
                $upload_detail['route'] = $extension;

                $upload_detail['file'] = $web_file;
                $upload_detail['extension'] = $tmp_uploaded_file->getClientOriginalExtension();
                $upload_detail['type'] = $media_manager->getFileType($upload_detail['extension']);
                $upload_detail['not_found'] = 0;

                if ($upload_detail['file'] !== '') {

                    if (!file_exists($upload_path . '/' . $original_name)) {
                        $tmp_uploaded_file->move($upload_path, $original_name);
                    }

                    $media_ids[] = $this->saveRecord('#__media_media', $upload_detail, array('file=:file'), array('file' => $web_file));
                }
            }
        }


        return $media_ids;
    }

    public function urlExist($route) {

        $baseModel = new BaseModel();

        return $baseModel->urlExist($route);
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
    public function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH, $data = '') {

        $baseModel = new BaseModel();

        return $baseModel->generateUrl($route, $parameters, $referenceType, $data);
    }

    public function redirectToRoute($route, $parameters = '', $status = '302') {

        $this->getSession()->set('is_redirect', true);

        $url = $this->generateUrl($route, $parameters);
        $redirect_response = new RedirectResponse($url, $status);
        $redirect_response->sendHeaders();
        exit;
    }

    public function loggingMessage($msg, $type = '') {

        $baseModel = new BaseModel();
        $baseModel->loggingMessage($msg, $type);
    }

    public function loggingException($exception) {

        $baseModel = new BaseModel();
        $baseModel->loggingException($exception);
    }

    private function getTwigObject($object_arr, $paths = '') {

        // $config['templates_base_dir'] = realpath(JPATH_TEMPLATES);
        // $paths[] = realpath(JPATH_ROOT . '/include/Kazist/views/blocks/');
        // $paths[] = realpath(JPATH_ROOT . '/include/Kazist/views/include/form_macro');
        // $twig = new Twig($config)

        $twig = new Twig();
        $twig->addExtension(new TwigExtension());
        $twig->setTemplatesPaths($paths, true);


        foreach ($object_arr as $key => $object) {
            $twig->set($key, $object);
        }


        $twig->set('twig_object', $twig);

        return $twig;
    }

    public function resizedImage($image, $width = '', $height = '', $type = '') {


        $mediamanager = new MediaManager();

        $image_path = JPATH_ROOT . $image;

        if (!is_file($image_path)) {
            $image = 'uploads/system/no-image.png';
        }

        if ($width || $height) {
            $image_path = JPATH_ROOT . $mediamanager->getFileImage($image);

            $web_filename = $mediamanager->getResizedImage($image, $width, $height, $type);
        } else {
            $web_filename = $image;
        }

        return WEB_ROOT . $web_filename . '?' . filemtime($image_path);
    }

}
