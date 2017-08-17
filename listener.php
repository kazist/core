<?php

use Symfony\Component\DependencyInjection\Reference;

$files = scandir(JPATH_ROOT . 'include/Kazist/Listener');

foreach ($files as $key => $file_name) {
    if (strpos($file_name, '.php')) {

        $name = str_replace('.php', '', $file_name);
        $listener_name = 'listener.' . strtolower($name);
        $class_name = 'Kazist\\Listener\\' . $name;

        $sc->register($listener_name, $class_name);
        $sc->getDefinition('dispatcher')
                ->addMethodCall('addSubscriber', array(new Reference($listener_name)))
        ;
    }
}

// Process Listeners From File
$listener_list = array();
$dir = new DirectoryIterator(JPATH_ROOT . '/applications');

foreach ($dir as $fileinfo) {
    if ($fileinfo->isDir() && !$fileinfo->isDot()) {

        $folder_path = JPATH_ROOT . '/applications/' . ucfirst($fileinfo->getFilename());

        if (file_exists($folder_path . '/listener.json')) {
            $listeners = json_decode(file_get_contents($folder_path . '/listener.json'));

            foreach ($listeners as $listener) {
                $listener_list[$listener->class] = $listener;
            }
        }
    }
}

// Process Listeners From Database
{

    $query = new Kazist\Service\Database\Query();

    try {

        $records = $query->select('sl.*')
                ->from('#__system_listeners', 'sl')
                ->orderBy('sl.ordering', 'ASC')
                ->loadObjectList();

        if (!empty($records)) {
            foreach ($records as $record) {

                if (array_key_exists($record->class, $listener_list)) {
                    unset($listener_list[$record->class]);
                }

                if ($record->published) {

                    $listener_name = strtolower(str_replace('\\', '.', $record->class));

                    $sc->register($listener_name, $record->class);
                    $sc->getDefinition('dispatcher')
                            ->addMethodCall('addSubscriber', array(new Reference($listener_name)))
                    ;
                }
            }
        }

        foreach ($listener_list as $listener) {

            $class_arr = explode('Code', $listener->class);

            $query->insert('#__system_listeners', 'sl');

            $query->setValue('title', ':title');
            $query->setParameter('title', $listener->title);

            $ordering = ($listener->ordering) ? $listener->ordering : 10;
            $query->setValue('ordering', ':ordering');
            $query->setParameter('ordering', $ordering);

            $query->setValue('class', ':class');
            $query->setParameter('class', $listener->class);

            $query->setValue('description', ':description');
            $query->setParameter('description', $listener->description);

            $query->setValue('extension_path', ':extension_path');
            $query->setParameter('extension_path', rtrim($class_arr[0], '\\'));

            $query->setValue('published', ':published');
            $query->setParameter('published', 1);

            $query->setValue('system_tracking_id', ':system_tracking_id');
            $query->setParameter('system_tracking_id', $listener->tracking_id);

            $query->setValue('is_modified', ':is_modified');
            $query->setParameter('is_modified', 0);

            $query->setValue('created_by', ':created_by');
            $query->setParameter('created_by', 1);

            $query->setValue('date_created', ':date_created');
            $query->setParameter('date_created', date('Y-m-d H:i:s'));

            $query->setValue('modified_by', ':modified_by');
            $query->setParameter('modified_by', 1);

            $query->setValue('date_modified', ':date_modified');
            $query->setParameter('date_modified', date('Y-m-d H:i:s'));

            $query->executeUpdate();
        }
    } catch (Exception $ex) {
        echo '<div class="alert alert-danger">Error While Loading Listeners<br>' . $ex->getMessage() . '</div>';
    }
}


