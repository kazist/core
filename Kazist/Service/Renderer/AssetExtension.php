<?php

/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Kazist\Service\Renderer;

defined('KAZIST') or exit('Not Kazist Framework');

/**
 * Twig extension class
 *
 * @since  1.0
 */
class AssetExtension extends \Twig_Extension {

    protected $container = array();

    public function __construct() {
        global $sc;

        $this->container = $sc;
    }

    /**
     * Returns the name of the extension.
     *
     * @return  string  The extension name.
     *
     * @since   1.0
     */
    public function getName() {
        return 'asset';
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

        //Media Behaviours
        $functions[] = new \Twig_SimpleFunction('set_assets', array($this, 'setAssets'));
        $functions[] = new \Twig_SimpleFunction('get_assets', array($this, 'getAssets'));
        $functions[] = new \Twig_SimpleFunction('get_all_assets', array($this, 'getAllAssets'));

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


        return $filters;
    }

    /* ---------------------------------------- Asset Behaviours ---------------------------- */

    public function setAssets($url, $category) {

        $assets = $this->container->get('session')->get('kazist_assets');

        $url_arr = array_reverse(explode('.', $url));
        $category_name = ($category <> '') ? $category : 'other';

        $assets[$url_arr[0]][$category_name][$url] = $url;

        $this->container->get('session')->set('kazist_assets', $assets);
    }

    public function getAssets($type, $category) {

        $assets = $this->container->get('session')->get('kazist_assets');
    }

    public function getAllAssets($types) {

        $assets = $this->container->get('session')->get('kazist_assets');
    }

}
