<?php

/**
 * @package    App\View\Renderer
 *
 * @copyright  Copyright (C) 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Kazist\Service\Renderer;

defined('KAZIST') or exit('Not Kazist Framework');

use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Kazist\KazistFactory;

/**
 * Twig class for rendering output.
 *
 * @since  1.0
 */
class Twig extends \Twig_Environment implements RendererInterface {

    /**
     * The renderer default configuration parameters.
     *
     * @var    array
     * @since  1.0
     */
    private $config = array('templates_base_dir' => JPATH_TEMPLATES, 'template_file_ext' => '.twig', 'twig_cache_dir' => 'cache/twig/', 'delimiters' => array('tag_comment' => array('{#', '#}'), 'tag_block' => array('{%', '%}'), 'tag_variable' => array('{{', '}}')), 'environment' => array());

    /**
     * The data for the renderer.
     *
     * @var    array
     * @since  1.0
     */
    private $data = array();

    /**
     * The templates location paths.
     *
     * @var    array
     * @since  1.0
     */
    private $templatesPaths = array();

    /**
     * Current template name.
     *
     * @var    string
     * @since  1.0
     */
    private $template;

    /**
     * Loads template from the filesystem.
     *
     * @var    \Twig_Loader_Filesystem
     * @since  1.0
     */
    private $twigLoader;
    protected $container = array();

    /**
     * Instantiate the renderer.
     *
     * @param   array  $config  The array of configuration parameters.
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    public function __construct($config = array()) {

        $factory = new KazistFactory();

        global $sc;

        $this->container = $sc;

        $document = $this->container->get('document');

        if (WEB_IS_ADMIN) {
            $view_folder = JPATH_ROOT . '/applications/' . $document->class . '/views/admin';
        } else {
            $view_folder = JPATH_ROOT . '/applications/' . $document->class . '/views';
        }

        if (is_dir($view_folder)) {
            $paths[] = realpath($view_folder);
        }

        $paths[] = realpath(JPATH_ROOT . 'include/Kazist/views/general');
        $paths[] = realpath(JPATH_ROOT . '/include/Kazist/views');
        $paths[] = realpath(JPATH_ROOT . '/include/Kazist/views/form_macro');
        $paths[] = realpath(JPATH_ROOT . '/include');
        $paths[] = realpath(JPATH_ROOT . '/applications');

        // $factory = new KazistFactory();
        // $session = $factory->getSession();
        // $config_setting = $session->get('config_setting');
        // Merge the config.
        $this->config = array_merge($this->config, $config);
        //  print_r($config_setting); exit;
        // if (isset($config_setting->cache->enable) && $config_setting->cache->enable) {
        $this->config['environment']['cache'] = JPATH_ROOT . '/cache/twig';
        $this->config['environment']['auto_reload'] = true;
        // }
        // Set the templates location path.
        $this->setTemplatesPaths($paths, true);
        $this->setTemplatesPaths($this->config['templates_base_dir'], true);


        try {
            $this->twigLoader = new \Twig_Loader_Filesystem($this->templatesPaths);
        } catch (\Twig_Error_Loader $e) {
            $factory->loggingMessage($e->getRawMessage());
            throw new \RuntimeException($e->getRawMessage());
        }

        parent::__construct($this->twigLoader, $this->config['environment']);

        $this->setFormBridge();
    }

    public function setFormBridge() {

        $document = $this->container->get('document');

        // Set up the Translation component
        $translator = new Translator('en');

        $translator->addLoader('xlf', new XliffFileLoader());
        $translator->addResource('xlf', VENDOR_FORM_DIR . '/Resources/translations/validators.en.xlf', 'en', 'validators');
        $translator->addResource('xlf', VENDOR_VALIDATOR_DIR . '/Resources/translations/validators.en.xlf', 'en', 'validators');

        $languages = json_decode(file_get_contents(JPATH_ROOT . 'applications/' . $document->class . 'language.json'), true);

        if (!empty($languages)) {
            $translator->addLoader('array', new \Symfony\Component\Translation\Loader\ArrayLoader());
            $translator->addResource('array', $languages, 'en');
        }

        // Set the templates location path.
        $this->setTemplatesPaths(VENDOR_TWIG_BRIDGE_DIR . '/Resources/views/Form', true);

// Set up the CSRF Token Manager
        $csrfTokenManager = new CsrfTokenManager();

        $formEngine = new TwigRendererEngine(array(DEFAULT_FORM_THEME));
        $formEngine->setEnvironment($this);
        // print_r($this); exit;
        $this->addExtension(new TranslationExtension($translator));
        $this->addExtension(new MediaExtension());
        $this->addExtension(new FollowExtension());
        $this->addExtension(new AssetExtension());
        $this->addExtension(new TwigExtension());
        $this->addExtension(new \Twig_Extension_StringLoader());
        $this->addExtension(
                new FormExtension(new TwigRenderer($formEngine, $csrfTokenManager))
        );
    }

    /**
     * Get the Lexer instance.
     *
     * @return  \Twig_LexerInterface  A Twig_LexerInterface instance.
     *
     * @since   1.0
     */
    public function getLexer() {
        if (null === $this->lexer) {
            $this->lexer = new \Twig_Lexer($this, $this->config['delimiters']);
        }

        return $this->lexer;
    }

    /**
     * get the data for the renderer.
     *
     * @param   mixed    $key     The variable name or an array of variable names with values.
     *
     * @return  Value.
     *
     * @since   1.0
     * @throws  \InvalidArgumentException
     */
    public function get($key) {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        } else {
            $globals = $this->getGlobals();
            if (isset($this->data[$key])) {
                return $globals[$key];
            } else {
                return false;
            }
        }
    }

    /**
     * Set the data for the renderer.
     *
     * @param   mixed    $key     The variable name or an array of variable names with values.
     * @param   mixed    $value   The value.
     * @param   boolean  $global  Is this a global variable?
     *
     * @return  Twig  Method supports chaining.
     *
     * @since   1.0
     * @throws  \InvalidArgumentException
     */
    public function set($key, $value = null, $global = false) {
        // $session = new Session();
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v, $global);
            }
        } else {
            if (!isset($value)) {
                //throw new \InvalidArgumentException('No value defined. :- '.$key);
            }
            // $session->set($key, $value, false, 'twigcontext');

            if ($global) {
                $this->addGlobal($key, $value);
            } else {

                $this->data[$key] = $value;
            }
        }

        return $this;
    }

    public function setGeneralData($key, $value = null) {
        if ($this->dataIsSet('general')) {
            $data = $this->get('general');
            $data[$key] = $value;
        } else {
            $data = array();
            $data[$key] = $value;
        }

        $this->set('general', $data);
    }

    /**
     * Check if data is set.
     *
     * @param   mixed  $key  The variable name.
     *
     * @return  return boolean.
     *
     * @since   1.0
     */
    public function dataIsSet($key) {

        if (isset($this->data[$key])) {
            return true;
        }

        return false;
    }

    /**
     * Unset a particular variable.
     *
     * @param   mixed  $key  The variable name.
     *
     * @return  Twig  Method supports chaining.
     *
     * @since   1.0
     */
    public function unsetData($key) {
        return $this->unsetData($key);
    }

    /**
     * Render and return compiled HTML.
     *
     * @param   string  $template  The template file name.
     * @param   array   $data      An array of data to pass to the template.
     *
     * @return  string  Compiled HTML.
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    public function render($template = '', array $data = array()) {

        $factory = new KazistFactory();

        if (!empty($template)) {
            $this->setTemplate($template);
        }

        if (!empty($data)) {
            $this->set($data);
        }

        try {
            return $this->load()->render($this->data);
        } catch (\Twig_Error_Loader $e) {
            $factory->loggingMessage($e->getRawMessage());
            throw new \RuntimeException($e->getRawMessage());
        }
    }

    /**
     * Display the compiled HTML content.
     *
     * @param   string  $template  The template file name.
     * @param   array   $data      An array of data to pass to the template.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function display($template = '', array $data = array()) {

        $factory = new KazistFactory();

        if (!empty($template)) {
            $this->setTemplate($template);
        }

        if (!empty($data)) {
            $this->set($data);
        }

        try {
            $this->load()->display($this->data);
        } catch (\Twig_Error_Loader $e) {
            $factory->loggingMessage($e->getRawMessage());
            throw $e;
        }
    }

    /**
     * Get the current template name.
     *
     * @return  string  The name of the currently loaded template file (without the extension).
     *
     * @since   1.0
     */
    public function getTemplate() {
        return $this->template;
    }

    /**
     * Add a path to the templates location array.
     *
     * @param   string  $path  Templates location path.
     *
     * @return  $this
     *
     * @since   1.0
     */
    public function addPath($path) {
        return $this->setTemplatesPaths($path, true);
    }

    /**
     * Set the template.
     *
     * @param   string  $name  The name of the template file.
     *
     * @return  Twig  Method supports chaining.
     *
     * @since   1.0
     */
    public function setTemplate($name) {
        $this->template = $name;

        return $this;
    }

    /**
     * Sets the paths where templates are stored.
     *
     * @param   string|array  $paths            A path or an array of paths where to look for templates.
     * @param   bool          $overrideBaseDir  If true a path can be outside themes base directory.
     *
     * @return  Twig
     *
     * @since   1.0
     */
    public function setTemplatesPaths($paths, $overrideBaseDir = false) {

        $factory = new KazistFactory();

        if (!is_array($paths)) {
            $paths = array($paths);
        }

        foreach ($paths as $path) {
            if ($overrideBaseDir) {
                $this->templatesPaths[] = $path;
            } else {
                $this->templatesPaths[] = $this->config['templates_base_dir'] . $path;
            }
        }

        // Reset the paths if needed.
        if (is_object($this->twigLoader)) {
            try {
                $this->twigLoader->setPaths($this->templatesPaths);
            } catch (\Twig_Error_Loader $e) {
                $factory->loggingMessage($e->getRawMessage());
                throw $e;
            }
        }

        return $this;
    }

    public function getContextData() {
        return $this->data;
    }

    /**
     * Load the template and return an output object.
     *
     * @return  object  Output object.
     *
     * @since   1.0
     */
    public function load() {
        return $this->loadTemplate($this->getTemplate());
    }

}
