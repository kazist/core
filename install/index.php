<?php

$this->doctrine->entity_path = JPATH_ROOT . 'applications/System/Flexviews/Code/Tables';
$this->doctrine->getEntityManager();

print_r('sdfd');
exit;

$this->doctrine->entity_path = JPATH_ROOT . 'applications/System/Flexviews/Positions/Code/Tables';
$this->doctrine->getEntityManager();

$this->doctrine->entity_path = JPATH_ROOT . 'applications/System/Routes/Flexviews/Code/Tables';
$this->doctrine->getEntityManager();

$this->doctrine->entity_path = JPATH_ROOT . 'applications/System/Crons/Code/Tables';
$this->doctrine->getEntityManager();

$this->doctrine->entity_path = JPATH_ROOT . 'applications/System/Languages/Code/Tables';
$this->doctrine->getEntityManager();

$this->doctrine->entity_path = JPATH_ROOT . 'applications/System/Subsets/Code/Tables';
$this->doctrine->getEntityManager();

$this->doctrine->entity_path = JPATH_ROOT . 'applications/System/Routes/Code/Tables';
$this->doctrine->getEntityManager();

$this->doctrine->entity_path = JPATH_ROOT . 'applications/System/Routes/Permissions/Code/Tables';
$this->doctrine->getEntityManager();

$this->doctrine->entity_path = JPATH_ROOT . 'applications/Users/Roles/Code/Tables';
$this->doctrine->getEntityManager();

$this->doctrine->entity_path = JPATH_ROOT . 'applications/System/Settings/Code/Tables';
$this->doctrine->getEntityManager();

$this->doctrine->entity_path = JPATH_ROOT . 'applications/System/Extensions/Code/Tables';
$this->doctrine->getEntityManager();
