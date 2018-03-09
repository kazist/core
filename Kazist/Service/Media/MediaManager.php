<?php

namespace Kazist\Service\Media;

defined('KAZIST') or exit('Not Kazist Framework');

use Kazist\KazistFactory;
use Kazist\Service\Media\SimpleImage;
use Kazist\Service\Database\Query;

class MediaManager {

    public $images_ext = array();
    public $video_ext = array();
    public $audio_ext = array();
    public $odf_ext = array();
    public $original_image = '';

    function __construct() {
        $this->images_ext = array('png', 'jpe', 'jpeg', 'jpg', 'gif', 'bmp', 'ico', 'tiff', 'tif', 'svg', 'svgz');
        $this->video_ext = array('3g2', '3gp', 'asf', 'asx', 'avi', 'flv', 'm4v', 'mov', 'mp4', 'mpg', 'rm', 'srt', 'swf', 'vob', 'wmv');
        $this->audio_ext = array('aif', 'iff', 'm3u', 'm4a', 'mid', 'mp3', 'mpa', 'ra', 'wav', 'wma');
        $this->odf_ext = array('odt', 'ods', 'odp', 'odg', 'odf');
    }

    function getFileImage($file, $extension = '') {

        $extension = ($extension !== '') ? $extension : $this->getFileExtension($file);
        $icon_dir = 'assets/images/file-icons/32px/';
  
        $filename = $icon_dir . $extension . '.png';
        $default_filename = $icon_dir . '_blank.png';
 
        if ($this->getFileType($extension) != 'image' || $extension == '') {

            if (file_exists(JPATH_ROOT . '/' . $filename)) {
                $file = $filename;
            } else {
                $file = $default_filename;
            }
        }

        $file = $this->getImageValidURl($file);

// print_r($file);
        return $file;
    }

    public function getImageValidURl($file) {

        return $file;
    }

    function getFileExtension($file) {
        $url = array_reverse(explode('.', $file));
        if (count($url) > 1) {
            return $url[0];
        } else {
            return '';
        }
    }

    function getFileName($file) {
        $url = array_reverse(explode('/', $file));

        return $url[0];
    }

    function getFileType($extension) {

        if (in_array($extension, $this->images_ext)) {
            return 'image';
        } elseif (in_array($extension, $this->video_ext)) {
            return 'video';
        } elseif (in_array($extension, $this->audio_ext)) {
            return 'audio';
        } elseif (in_array($extension, $this->odf_ext)) {
            return 'file';
        } else {
            return 'file';
        }
    }

    function getMediaAdditionalDetails($media_id) {


        if (is_array($media_id)) {
            $media_id = implode(', ', $media_id);
        }

        $query = new Query();
        $query->select('mm.file as media_file, mm.title as media_title');
        $query->from('#__media_media', 'mm');
        $query->where('mm.id=:id');
        $query->setParameter('id', $media_id);

        $record = $query->loadObject();

        if ($record->media_title == '') {
            $record->media_title = $this->getFileName($record->media_file);
        }

        $record->media_icon = $this->getFileImage($record->media_file);
//print_r($record); exit;

        return $record;
    }

    function updateMedia($media_id, $extension_path, $source_field_name, $file_name, $web_path) {

        global $sc;

        //TODO work on get subset by $extension_path

        $factory = new KazistFactory;

        $extension_path = str_replace(array('/', '\\'), '.', $extension_path);

        $document = $sc->get('document');

        $subset_id = $document->subset_id;

        $existing_media = $factory->getRecord('#__media_media', 'mm', array('file=:file'), array('file' => $web_path));

        if (is_object($existing_media)) {
            $media_id = $existing_media->id;
        }

        $tmp_object = new \stdClass;
        $tmp_object->subset_id = $subset_id;
        $tmp_object->route = $extension_path;
        $tmp_object->field_name = $source_field_name;
        $tmp_object->title = $file_name;
        $tmp_object->file = $web_path;

        if ($media_id) {
            $tmp_object->id = $media_id;
        }

        $id = $factory->saveRecord('#__media_media', $tmp_object);

        return $id;
    }

    public function saveMediaUsage($record_id, $medias, $subset_id) {
        $factory = new KazistFactory;

        $posted_medias = array();

        if (!empty($medias)) {
            foreach ($medias as $key => $media) {

                $tmpobject = new \stdClass();
                $tmpobject->record_id = $record_id;
                $tmpobject->media_id = $media;
                $tmpobject->subset_id = $subset_id;
                $posted_medias[] = $media;

                $exist_id = $this->checkMediaUsageExist($tmpobject);

                if ($exist_id) {
                    $tmpobject->id = $exist_id;
                }

                $factory->saveRecord('#__media_usage', $tmpobject);
            }

            $query = new Query();
            $query->delete('#__media_usage');
            $query->where('media_id NOT IN (:media_id)');
            $query->andWhere('record_id = :record_id');
            $query->andWhere('subset_id = :subset_id');
            $query->setParameter('media_id', implode(',', $posted_medias));
            $query->setParameter('record_id', $record_id);
            $query->setParameter('subset_id', $subset_id);
            $query->execute();
        }
    }

    public function checkMediaUsageExist($tmpobject) {
        $factory = new KazistFactory;
        $db = $factory->getDatabase();

        $query = new Query();
        $query->select('id');
        $query->from('#__media_usage', 'mu');
        $query->where('mu.record_id=:record_id');
        $query->where('mu.media_id=:media_id');
        $query->where('mu.subset_id=:subset_id');
        $query->setParameter('record_id', $tmpobject->record_id);
        $query->setParameter('media_id', $tmpobject->media_id);
        $query->setParameter('subset_id', $tmpobject->subset_id);
        $db->setQuery($query);

        $usage_id = $db->loadResult();

        return $usage_id;
    }

    function getUploadDir($extension_path) {

        $uploaddir = 'uploads/';

        if ($extension_path <> '') {
            $uploaddir = $uploaddir . str_replace('.', '/', $extension_path) . '/';
        }

        if (!is_dir($uploaddir)) {
            $oldmask = umask(0);
            mkdir(JPATH_ROOT . '/' . $uploaddir, 0777, true);
            umask($oldmask);
        }

        return $uploaddir;
    }

//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXxxxxx   Resized Image XXXXXXXXXXXXXXXXXXXXX

    public function getResizedImage($url, $width = '', $height = '', $type = '') {


        $factory = new KazistFactory();

        $is_resize = false;
        $new_url = '';
        $url_arr = array_reverse(explode('/', $url));
        $original_image = $this->original_image = $url_arr[0];

        unset($url_arr[0]);

        $cur_folder = implode('/', array_reverse($url_arr));
        $new_folder = $cur_folder . '/resized';

        $factory->makeDir(JPATH_ROOT . '/' . $new_folder);

        $large_image = $new_folder . '/' . $original_image;

        $small_image = $new_folder . '/' . $this->getImageName($original_image, $width, $height, $type);
        $original_image = $cur_folder . '/' . $original_image;
        $new_url = $original_image;

        $original_image_location = JPATH_ROOT . '/' . $original_image;
        $large_image_location = JPATH_ROOT . '/' . $large_image;
        $small_image_location = JPATH_ROOT . '/' . $small_image;

        if (getimagesize($original_image_location)) {

            try {

                if (file_exists($large_image_location)) {
                    $new_url = $large_image;
                } elseif (!file_exists($large_image_location)) {
                    $is_resized = $this->resizeImage($original_image_location, $large_image_location, $width, $height, $type);
                    $new_url = ($is_resized) ? $large_image : $new_url;
                }

                if (file_exists($small_image_location)) {
                    $new_url = $small_image;
                } elseif (!file_exists($small_image_location) && ($height || $width)) {
                    $is_resized = $this->resizeImage($original_image_location, $small_image_location, $width, $height, $type);
                    $new_url = ($is_resized) ? $small_image : $new_url;
                }
            } catch (Exception $e) {
                $new_url = $url;
            }
        }

        return $this->getImageValidURl($new_url);
    }

    public function resizeImage($original_image_location, $new_image_location, $width, $height, $type) {

        if (file_exists($new_image_location) || !getimagesize($original_image_location)) {
            return false;
        }

        try {

            $simpleimage = new SimpleImage($original_image_location);

            switch ($type) {
                case 'bestfit':
                    $width = ($width) ? $width : $height;
                    $height = ($height) ? $height : $width;

                    $simpleimage->best_fit($height, $width)->save($new_image_location);

                    break;
                case 'fit':
                    if ($width && $height) {
                        $simpleimage->fit_to_width($width)->save($new_image_location);
                    } elseif ($height) {
                        $simpleimage->fit_to_height($height)->save($new_image_location);
                    } elseif ($width) {
                        $simpleimage->fit_to_width($width)->save($new_image_location);
                    }

                    break;

                default:

                    $width = ($width) ? $width : $height;
                    $height = ($height) ? $height : $width;

                    $simpleimage->thumbnail($width, $height)->save($new_image_location);
                    break;
            }


            if ($width && $height) {

                $img_height = $simpleimage->get_height();
                $img_width = $simpleimage->get_width();

                if ($img_height < $img_width) {
                    $simpleimage->thumbnail($width, $height)->save($new_image_location);
                } else {
                    $simpleimage->thumbnail($width, $height)->save($new_image_location);
                }

                // $simpleimage->resize($width, $height)->save($new_image_location);
            } else {
                $simpleimage->save($new_image_location);
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function getImageName($image, $width, $height, $type) {

        $image_name = '';

        $image_arr = explode('.', $image);
        $type_str = $type . '-';

        if ($width && $height) {
            $image_name = $image_arr[0] . '-' . $type_str . $width . 'x' . $height . '.' . $image_arr[1];
        } elseif ($height) {
            $image_name = $image_arr[0] . '-' . $type_str . $height . '-h' . '.' . $image_arr[1];
        } elseif ($width) {
            $image_name = $image_arr[0] . '-' . $type_str . $width . '-w' . '.' . $image_arr[1];
        } else {
            $image_name = $image;
        }

        return $image_name;
    }

    public function getActions() {

        $query = new Query();
        $query->select('mz.*');
        $query->from('#__media_size', 'mz');

        $records = $query->loadObjectList();

        return $records;
    }

    public function getActionByActionName($action_name) {

        $query = new Query();
        $query->select('ms.*');
        $query->from('#__media_size', 'ms');
        $query->where('ms.action_name=:action_name');
        $query->setParameter('action_name', $action_name);

        $record = $query->loadObject();

        return $record;
    }

}
