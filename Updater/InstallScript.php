<?php

/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Updater;

use Composer\Script\Event;

/**
 * Post-install class triggered during 'compser install' to set up system dependencies
 *
 * @since  1.0
 */
class InstallScript {

    public static function postInstall(Event $event) {

        // Check if a config.json file exists, copy the config.dist.json if not
        if (!file_exists('config/config.json') && file_exists('config/config.dist.json')) {
            copy('config/config.dist.json', 'config/config.json');
        }

        // Make sure the assets directory exists
        if (!is_dir('assets')) {
            mkdir('assets', 0755);
        }

        $src_themesPath = 'vendor/kazist/themes/';
        $desc_themesPath = 'themes/';
        InstallScript::copyRecursively($src_themesPath, $desc_themesPath);
    }

    public static function copyRecursively($source, $dest) {

        InstallScript::createdDir($dest);

        if (is_dir($source)) {

            $dir = new \DirectoryIterator($source);

            foreach ($dir as $fileinfo) {

                $file_name = $fileinfo->getFilename();
                if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                    $new_source = $source . '/' . $file_name;
                    $new_dest = $dest . '/' . $file_name;
                    InstallScript::copyRecursively($new_source, $new_dest);
                } elseif (!$fileinfo->isDir() && !$fileinfo->isDot()) {
                    copy($source . '/' . $file_name, $dest . '/' . $file_name);
                }
            }
        }
    }

    public static function createdDir($path) {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
            return true;
        } else {
            return false;
        }
    }

}
