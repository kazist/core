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
class MediaExtension extends \Twig_Extension {

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
        return 'media';
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
        $functions[] = new \Twig_SimpleFunction('set_media_exist', array($this, 'setMediaExist'));
        $functions[] = new \Twig_SimpleFunction('get_media_exist', array($this, 'getMediaExist'));
        $functions[] = new \Twig_SimpleFunction('clear_media_exist', array($this, 'clearMediaExist'));

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

    /* ---------------------------------------- Media Behaviours ---------------------------- */

    /**
     * set media exist to an array.
     *
     * @param   string  $string  media exist.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function setMediaExist($media_exist) {

        $this->container->get('session')->set('media_exist', $media_exist);

        return;
    }

    /**
     * Get get of media exist for current session.
     *
     * @param   null.
     *
     * @return  string
     *
     * @since   1.0
     */
    public function getMediaExist() {
        if ($this->container->get('session')->get('media_exist')
        ) {
            return true;
        } else {
            return false;
        }
    }

}
