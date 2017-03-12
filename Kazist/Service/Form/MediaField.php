<?php

namespace Kazist\Service\Form;

defined('KAZIST') or exit(DIE_MSG);

use Kazist\KazistFactory;
use Kazist\Service\Media\MediaManager;
use Kazist\Service\Database\Query;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class MediaField Extends BaseField {

    public function getEditData($field, $item) {

        $mediamanager = new MediaManager();

        if ($field['parameters']['save']['saving_type'] == 'multiple') {

            $tmp_array = array();

            $ids = $this->getSavedRecords($field, $item);

            $query = new Query();
            $query->from('#__media_media', 'mm');
            $query->select('*');
            if (!empty($ids)) {
                $query->where('id IN (' . implode(',', $ids) . ')');
            } else {
                $query->where('1=-1');
            }
            $records = $query->loadObjectList();

            foreach ($records as $record) {
                $data = new \stdClass();
                $data->media_id = $record->id;
                $data->media_image = $mediamanager->getFileImage($record->file);
                $data->media_file = $item->file;
                $data->media_title = $item->title;
                $tmp_array[] = $data;
            }

            $data = $tmp_array;
        } else {

            $field_name = $field['name'];

            $field_name_file = $field_name . '_file';
            $field_name_title = $field_name . '_title';

            $data = new \stdClass();
            $data->media_id = $item->$field_name;
            $data->media_image = $mediamanager->getFileImage($item->$field_name_file);
            $data->media_file = $item->$field_name_file;
            $data->media_title = $item->$field_name_title;
        }

        return $data;
    }

}
