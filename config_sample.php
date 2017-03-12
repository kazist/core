<?php

define('DEFAULT_FORM_THEME', 'form_div_layout.html.twig');

/* -------------------------- Database settings ------------------- */

$sc->setParameter('database.driver', 'pdo_mysql');
$sc->setParameter('database.host', 'localhost');
$sc->setParameter('database.user', 'root');
$sc->setParameter('database.password', 'developer');
$sc->setParameter('database.name', 'kazist');
$sc->setParameter('database.prefix', 'kazist_');

/* -------------------------- Cache settings ------------------- */

$sc->setParameter('cache.enable', '1');
$sc->setParameter('cache.folder', 'files/cache/twig');

/* -------------------------- Ftp settings ------------------- */

$sc->setParameter('ftp.host', 'localhost');
$sc->setParameter('ftp.port', '21');
$sc->setParameter('ftp.username', 'ftproot');
$sc->setParameter('ftp.password', 'ftproot');
$sc->setParameter('ftp.directory', 'kazist/');

/* -------------------------- Seo settings ------------------- */

$sc->setParameter('system.logging', false);
$sc->setParameter('system.debugging', false);
$sc->setParameter('seo.title', 'Kazist');
$sc->setParameter('seo.description', 'Framework For Cool Systems');
$sc->setParameter('seo.system_title_on_pages', '1');
$sc->setParameter('seo.system_title_at', 'end');
$sc->setParameter('seo.enable', '1');
$sc->setParameter('seo.extension', 'html');
$sc->setParameter('seo.rewrite', '1');
$sc->setParameter('seo.minified', '0');
$sc->setParameter('seo.merge', '0');
$sc->setParameter('seo.browser_cache', '1');

/* -------------------------- Themes settings ------------------- */

$sc->setParameter('theme.default', 'awesome');

/* -------------------------- System settings ------------------- */

$sc->setParameter('system.timeout', '15');
$sc->setParameter('system.stage', 'production');
$sc->setParameter('system.list_limit', '10');
$sc->setParameter('system.gzip', '0');
$sc->setParameter('system.title', 'Kaziframework');
$sc->setParameter('system.offset', 'awesome');
$sc->setParameter('system.seo_extension', 'html');
$sc->setParameter('system.seo_rewrite', '1');
$sc->setParameter('system.error_reporting', 'E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_ERROR|E_CORE_ERROR');
$sc->setParameter('system.error_display', 'on');

/* -------------------------- Company settings ------------------- */

$sc->setParameter('company.name', 'Kazist');
$sc->setParameter('company.postal_address', '232323');
$sc->setParameter('company.postal_code', '00400');
$sc->setParameter('company.physical_location', 'Nairobi Kenya');
$sc->setParameter('company.phone', '254000000000');
$sc->setParameter('company.telephone', '254000000000');
$sc->setParameter('company.email', 'info@example.com');


