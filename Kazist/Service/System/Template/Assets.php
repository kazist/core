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
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\AssetManager;

class Assets {

    public $container = '';
    public $request = '';

//put your code here
    function __construct($container, $request) {
        $this->container = $container;
        $this->request = $request;
    }

    public function manageAssets($response_content) {

        $asset_html = '';
        $asset_list = $this->container->get('session')->get('kazist_assets');
        $asset_merge = $this->container->getParameter('asset.merge');


        foreach ($asset_list as $file_ext => $type_files) {


            foreach ($type_files as $priority => $files) {
                foreach ($files as $file_key => $file) {
                    $asset_html .= $this->getLinkHtml($file_ext, $file, $response_content);
                }
            }

            $response_content = $this->appendAssetsToContent($response_content, $asset_html, $file_ext);

            $asset_html = '';
        }

        $response_content = $this->appendObject($response_content);


        return $response_content;
    }

    public function appendObject($response_content) {

        $document = $this->container->get('document');



        $kazist['web_root'] = WEB_ROOT;
        $kazist['web_base'] = WEB_BASE;
        $kazist['web_home'] = WEB_HOME;
        $kazist['web_admin_home'] = WEB_ADMIN_HOME;
        $kazist['web_front_home'] = WEB_FRONT_HOME;
        $kazist['web_is_admin'] = WEB_IS_ADMIN;
        $kazist['web_is_homepage'] = WEB_IS_HOMEPAGE;
        $kazist['extension_path'] = $document->extension_path;
        $kazist['main_route'] = $document->main_route;
        $kazist['root_route'] = $document->root_route;
        $kazist['base_route'] = $document->base_route;
        $kazist['current_url'] = $document->current_url;
        $kazist['user_id'] = $document->user->id;
        $kazist['record_id'] = $document->record_id;

        unset($document->user);

        $response_content = $response_content . '<script> var kazist_document = ' . json_encode($kazist) . ';</script>';

        return $response_content;
    }

    public function manageAssetsOld($response_content) {

        $asset_html = '';
        $asset_list = $this->container->get('session')->get('kazist_assets');
        $asset_merge = $this->container->getParameter('asset.merge');

        foreach ($asset_list as $cat_key => $asset_cat) {

            foreach ($asset_cat as $type_key => $asset_type) {

                $asset_file_obj = array();

                $asset_web_path = WEB_ROOT . 'cache/assets/' . $cat_key . '/';
                $asset_path = JPATH_ROOT . 'cache/assets/' . $cat_key . '/';

                $cache_file = $asset_path . $type_key . '.' . $cat_key;
                $web_cache_file = $asset_web_path . $type_key . '.' . $cat_key;

                foreach ($asset_type as $key => $asset_file) {

                    if (!$asset_merge || $type_key === 'core') {
                        $asset_html .= $this->getLinkHtml($cat_key, WEB_ROOT . $asset_file, $response_content);
                    } else {

                        $asset_file_arr = array_reverse(explode('/', $asset_file));
                        $file_name_min = (strpos($asset_file_arr[0], '.min')) ? $asset_file_arr[0] : str_replace('.' . $cat_key, '.min.' . $cat_key, $asset_file_arr[0]);
                        $asset_file_arr[0] = $file_name_min;
                        $asset_file_min_arr = array_reverse($asset_file_arr);
                        $asset_file_min = implode('/', $asset_file_min_arr);

                        $new_asset_file = (file_exists($asset_file_min)) ? $asset_file_min : $asset_file;

                        if (file_exists($asset_file_min)) {
                            $asset_file_obj[] = new FileAsset($new_asset_file);
                        }
                    }
                }


                if (!empty($asset_file_obj)) {
                    $asset_collection = new AssetCollection($asset_file_obj);

                    $this->makeDir($asset_path);
                    $content = $asset_collection->dump();
                    file_put_contents($cache_file, $content);

                    $asset_html .= $this->getLinkHtml($cat_key, $web_cache_file, $response_content);
                }
            }
        }

        $response_content = $this->appendAssetsToContent($response_content, $asset_html);

        return $response_content;
    }

    public function appendAssetsToContent($response_content, $asset_html, $file_ext) {

        if ($file_ext === 'css' && strpos($response_content, '</body>')) {
            $response_content = str_replace('</head>', $asset_html . "\n" . '</head>', $response_content);
        } elseif (strpos($response_content, '</body>')) {
            $response_content = str_replace('</body>', $asset_html . "\n" . '</body>', $response_content);
        } elseif (strpos($response_content, '</html>')) {
            $response_content = str_replace('</html>', $asset_html . "\n" . '</html>', $response_content);
        } else {
            $response_content = $response_content . $asset_html;
        }

        return $response_content;
    }

    public function getLinkHtml($type, $file, $response_content) {

        $file_html = '';

        $system_path = JPATH_ROOT . $file;
        $web_path = WEB_ROOT . $file . '?' . filemtime($system_path);

        if (!strpos($response_content, $file)) {
            switch ($type) {
                case 'css':
                    $file_html = '<link rel="stylesheet" type="text/css" href="' . $web_path . '"/>' . "\n";
                    break;
                case 'js':
                    $file_html = '<script src="' . $web_path . '"></script>' . "\n";
                    break;
            }
        }

        return $file_html;
    }

    public function makeDir($dir) {
        if (!is_dir($dir)) {
            $oldmask = umask(0);
            mkdir($dir, 0775, true);
            umask($oldmask);
        }
    }

}
