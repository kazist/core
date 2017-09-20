<?php

define('JPATH_ROOT', realpath(__DIR__ . '/../') . '/');
define('JPATH_SETUP', JPATH_ROOT . 'include/Setup');
define('VIEW_SIDE', 'frontend');
define('KAZIST', true);
putenv('TMPDIR=' . JPATH_ROOT . 'cache/tmp');

define('VENDOR_DIR', JPATH_ROOT . 'vendor/');
define('VENDOR_FORM_DIR', VENDOR_DIR . 'symfony/form');
define('VENDOR_VALIDATOR_DIR', VENDOR_DIR . 'symfony/validator');
define('VENDOR_TWIG_BRIDGE_DIR', VENDOR_DIR . 'symfony/twig-bridge');

use Kazist\Service\NewRouterCollection;

$dir = new DirectoryIterator(JPATH_ROOT . '/applications');

foreach ($dir as $fileinfo) {
    if ($fileinfo->isDir() && !$fileinfo->isDot()) {
        $loader->set(ucfirst($fileinfo->getFilename()), array(JPATH_ROOT . '/applications'));
    }
}

$loader->register(true);

$session = $sc->get('session');

$session->remove('routes_cached');

/* $cache_routes = $sc->getParameter('system.cache_routes');

  if ($cache_routes) {

  $routes_cached_time = $session->get('routes_cached_time');

  if ($routes_cached_time == '' || time() > $routes_cached_time) {

  $routes = new NewRouterCollection();
  $tmp_routes = clone $routes;
  $session->set('routes_cached', $tmp_routes);
  $session->set('routes_cached_time', strtotime('+1 hours'));
  } else {
  $routes = $session->get('routes_cached');
  }
  } else {
  $routes = new NewRouterCollection();
  } */

$routes = new NewRouterCollection();

return $routes;
