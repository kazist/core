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


$query = new Kazist\Service\Database\Query();

try {
    $records = $query->select('sl.*')
            ->from('#__system_listeners', 'sl')
            ->where('sl.published=1')
            ->loadObjectList();

    if (!empty($records)) {
        foreach ($records as $record) {

            $listener_name = strtolower(str_replace('\\', '.', $record->class));

            $sc->register($listener_name, $record->class);
            $sc->getDefinition('dispatcher')
                    ->addMethodCall('addSubscriber', array(new Reference($listener_name)))
            ;
        }
    }
} catch (Exception $ex) {
    echo '<div class="alert alert-danger">Error While Loading Listeners<br>' . $ex->getMessage() . '</div>';
}


