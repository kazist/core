<?php

/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Kazist\Service\Renderer;

defined('KAZIST') or exit('Not Kazist Framework');

use Kazist\StringModification;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Kazist\Service\Media\MediaManager;
use Kazist\Service\System\System;
use Kazist\KazistFactory;

/**
 * Twig extension class
 *
 * @since  1.0
 */
class TwigExtension extends \Twig_Extension {

    /**
     * All javascript url list
     *
     * @var    Javascript
     * @since  1.0
     */
    protected $javascript = array();
    protected $container = array();

    /**
     * Returns the name of the extension.
     *
     * @return  string  The extension name.
     *
     * @since   1.0
     */
    public function __construct() {
        global $sc;

        $this->container = $sc;
    }

    public function getName() {
        return 'kazist';
    }

    /**
     * Returns a list of global variables to add to the existing list.
     *
     * @return  array  An array of global variables.
     *
     * @since   1.0
     */
    public function getGlobals() {

        $global = array();

        $global['web_root'] = rtrim(WEB_ROOT, '/') . '/';
        $global['web_base'] = WEB_BASE;
        $global['request'] = REQUEST;
        $global['web_front_home'] = WEB_FRONT_HOME;
        $global['web_admin_home'] = WEB_ADMIN_HOME;
        $global['web_is_admin'] = WEB_IS_ADMIN;
        $global['web_home'] = WEB_HOME;
        $global['document'] = $this->container->get('document');

        return $global;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return  array  An array of functions.
     *
     * @since   1.0
     */
    public function getFunctions() {
        $functions = array();

        $functions[] = new \Twig_SimpleFunction('idname', array($this, 'getIdName'));
        $functions[] = new \Twig_SimpleFunction('classname', array($this, 'getClassName'));
        $functions[] = new \Twig_SimpleFunction('fieldname', array($this, 'getFieldName'));
        $functions[] = new \Twig_SimpleFunction('underscore', array($this, 'getUnderscore'));
        $functions[] = new \Twig_SimpleFunction('friendly_url', array($this, 'getFriendlyUrl'));
        $functions[] = new \Twig_SimpleFunction('kazidump', array($this, 'getKaziDump'));
        $functions[] = new \Twig_SimpleFunction('truncate', array($this, 'truncate'));
        $functions[] = new \Twig_SimpleFunction('is_granted_access', array($this, 'isGrantedAcess'));
        $functions[] = new \Twig_SimpleFunction('resized_image', array($this, 'resizedImage'));
        $functions[] = new \Twig_SimpleFunction('generate_url', array($this, 'generateUrl'));
        $functions[] = new \Twig_SimpleFunction('url', array($this, 'generateUrl'));
        $functions[] = new \Twig_SimpleFunction('url_exist', array($this, 'urlExist'));
        $functions[] = new \Twig_SimpleFunction('get_session', array($this, 'getSession'));
        $functions[] = new \Twig_SimpleFunction('get_context', array($this, 'getContext'));
        $functions[] = new \Twig_SimpleFunction('get_setting', array($this, 'getSetting'));
        $functions[] = new \Twig_SimpleFunction('get_flash_bags', array($this, 'getFlashBags'));
        $functions[] = new \Twig_SimpleFunction('is_admin', array($this, 'isAdmin'));
        $functions[] = new \Twig_SimpleFunction('get_user', array($this, 'getUser'));
        $functions[] = new \Twig_SimpleFunction('get_container', array($this, 'getContainer'));
        $functions[] = new \Twig_SimpleFunction('json_decode', array($this, 'getJsonDecode'));
        $functions[] = new \Twig_SimpleFunction('base64_decode', array($this, 'getBase64Encode'));

        $functions[] = new \Twig_SimpleFunction('field_override_exist', array($this, 'fieldOverrideExist'));
        $functions[] = new \Twig_SimpleFunction('field_macro_exist', array($this, 'formFieldMacroExist'));

        $functions[] = new \Twig_SimpleFunction('callFunction', array($this, 'callFunction'));
        $functions[] = new \Twig_SimpleFunction('addTwigPath', array($this, 'addTwigPath'));
        $functions[] = new \Twig_SimpleFunction('hash_hmac', array($this, 'hash_hmac'));
        $functions[] = new \Twig_SimpleFunction('md5', array($this, 'md5'));

        return $functions;
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return  array  An array of filters
     *
     * @since   1.0
     */
    public function getFilters() {
        $filters = array();

        $filters[] = new \Twig_SimpleFilter('classname', array($this, 'getClassName'));
        $filters[] = new \Twig_SimpleFilter('fieldname', array($this, 'getFieldName'));
        $filters[] = new \Twig_SimpleFilter('underscore', array($this, 'getUnderscore'));
        $filters[] = new \Twig_SimpleFilter('friendly_url', array($this, 'getFriendlyUrl'));
        $filters[] = new \Twig_SimpleFilter('kazidump', array($this, 'getKaziDump'));
        $filters[] = new \Twig_SimpleFilter('truncate', array($this, 'truncate'));
        $filters[] = new \Twig_SimpleFilter('timeago', array($this, 'getTimeAgo'));

        return $filters;
    }

    public function getUser() {
        $factory = new KazistFactory();
        return $factory->getUser();
    }

    public function isAdmin() {
        $factory = new KazistFactory();
        return $factory->isAdmin();
    }

    public function getContainer() {
        return $this->container;
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

    public function addTwigPath($path) {

        $factory = new KazistFactory();

        $twig = $this->container->get('twig');

        if (is_object($twig)) {

            if (!is_dir($path)) {
                $path = JPATH_ROOT . '/' . str_replace('.', '/', $path);
            }

            $twig->addPath($path);
        } else {
            $msg = 'Twig Object Not Found.';
            $factory->enqueueMessage($msg, 'info');
            return $msg;
        }
    }

    public function callFunction($class_name, $function_name, $args = '') {

        try {

            $system = new System();
            $data = $system->callFunctionDynamically($class_name, $function_name, $args);
        } catch (\Exception $e) {

            $data = 'error loading:' . str_replace('.', '\\', $class_name) . '::' . $function_name . '    ' . $e->getMessage();
        }

        return $data;
    }

    public function getFlashBags() {

        $messages = $this->container->get('session')->getFlashBag()->peekAll();

        return $messages;
    }

    public function isGrantedAcess($route, $action) {
        return true;
    }

    public function fieldOverrideExist($twig_name) {

        $exist = false;

        $document = $this->container->get('document');

        if (WEB_IS_ADMIN) {
            $file = JPATH_ROOT . '/applications/' . $document->class . '/views/admin/' . $twig_name;
        } else {
            $file = JPATH_ROOT . '/applications/' . $document->class . '/views/' . $twig_name;
        }

        if (file_exists($file)) {
            $exist = true;
        }


        return $exist;
    }

    public function getSession($item_name) {

        $data = $this->container->get('session')->get($item_name);

        return $data;
    }

    public function getContext($item_name) {

        $context = $this->container->get('session')->get('twig_context');

        return $context[$item_name];
    }

    public function urlExist($route) {
        $factory = new KazistFactory();

        return $factory->urlExist($route);
    }

    public function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH, $data = '') {

        $factory = new KazistFactory();

        return $factory->generateUrl($route, $parameters, $referenceType, $data);
    }

    public function getIdName($id, $name) {

        if ($id == '') {
            $id = str_replace('.', '_', $name);
        }

        return $id;
    }

    public function getClassName($class, $name, $has_form_class = true) {
        $form_class = 'form-control input-sm ';

        if (!$has_form_class) {
            $form_class = '';
        }

        if ($class == '') {
            $class = $form_class . str_replace('.', '_', $name);
        } else {
            if ($class[0] == ' ') {
                $class = $form_class . $class;
            }
        }

        return $class;
    }

    public function getFieldName($variable) {
        $prefix = 'form';

        $has_first_space = ($variable[0] == ' ') ? true : false;

        $variable_str = str_replace(' ', '', $variable);
        $variable_arr = explode('.', $variable_str);

        if ($variable_arr[0] == 'search' || $has_first_space) {
            $prefix = $variable_arr[0];
            unset($variable_arr[0]);
        }

        $fieldname_arr = (!empty($variable_arr)) ? '[' . implode('][', $variable_arr) . ']' : '';
        $fieldname = $prefix . $fieldname_arr;

        return $fieldname;
    }

    public function getUnderscore($variable) {
        $str_modification = new StringModification;

        return $str_modification->underscore($variable);
    }

    function truncate($name, $length) {

        if (strlen($name) > $length) {
            return substr($name, 0, $length) . '...';
        }
        return $name;
    }

    public function formFieldMacroExist($variable) {
        $file = JPATH_ROOT . '/include/Kazist/views/form_macro/' . $variable;

        if (!file_exists($file)) {
            return false;
        }
        return true;
    }

    public function getResizedImage($url, $width = '', $height = '', $type = '') {

        $mediamanager = new MediaManager();

        $path_url = JPATH_ROOT . '/' . $url;

        if (!is_file($path_url) || !getimagesize($path_url)) {
            $url = 'uploads/system/no-image.png';
        }

        $web_filename = $mediamanager->getResizedImage($url, $width, $height, $type);

        return $web_filename . '?' . filemtime($path_url);

        //return $url;
    }

    public function getSetting($string, $default = '') {

        $factory = new KazistFactory();
        $value = $factory->getSetting($string, $default);
        return $value;
    }

    public function getBase64Encode($variable) {

        return base64_encode($variable);
    }

    public function getJsonDecode($variable) {

        return json_decode($variable);
    }

    public function getFriendlyUrl($variable) {
        return $variable;
    }

    public function getKaziDump($variable) {
        return var_dump($variable);
    }

    public function getTimeAgo($datetime) {

        $time = time() - strtotime($datetime);

        if ($time < 0) {
            $to_come_str = 'ahead';
            $time = abs($time);
        } else {
            $to_come_str = 'ago';
        }

        $units = array(
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        );

        foreach ($units as $unit => $val) {
            if ($time < $unit)
                continue;
            $numberOfUnits = floor($time / $unit);
            return ($val == 'second') ? 'a few seconds ' . $to_come_str :
                    (($numberOfUnits > 1) ? $numberOfUnits : '1')
                    . ' ' . $val . (($numberOfUnits > 1) ? 's' : '') . ' ' . $to_come_str;
        }
    }

    public function hash_hmac($algorithm, $datastring, $hashkey) {
        return hash_hmac($algorithm, $datastring, $hashkey);
    }

    public function md5($string) {
        return md5($string);
    }

}
