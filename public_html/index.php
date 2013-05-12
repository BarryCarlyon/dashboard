<?php

define('DASHBOARD_CACHE_PATH', __DIR__ . '/../cache/');
define('DASHBOARD_URL_ROOT', dirname($_SERVER['SCRIPT_NAME']));
define('DASHBOARD_URL_MODULES', str_replace('//', '/', DASHBOARD_URL_ROOT . '/modules/'));
define('DASHBOARD_MODULES_PATH', __DIR__ . '/modules/');
define('DASHBOARD_LIB_PATH', __DIR__ . '/../lib/');

include(DASHBOARD_LIB_PATH . 'module.class.php');

$widgets = array();
$body = '';
// module load
$dir = new FilesystemIterator(DASHBOARD_MODULES_PATH);
foreach ($dir as $path => $fileinfo) {
    if ($fileinfo->isDir()) {
        $base = basename($path);
        if (is_file($path . '/' . $base . '.php')) {
            include($path . '/' . $base . '.php');
            $call = $base . 'Module';
            $widgets[$base] = new $call();
        }
    }
}

// run ajax
include(DASHBOARD_LIB_PATH . 'ajax.php');

$enabled_modules = array();

$state = DASHBOARD_CACHE_PATH . 'state.json';
if (is_file($state)) {
    $data = file_get_contents($state);
    $data = json_decode($data,true);

    $enabled_modules = $data;
}

// cache/write test
$cache = DASHBOARD_CACHE_PATH . 'test';
if (false === @fopen($cache, 'w')) {
    $cache_test = '<div id="initilise" class="error_normal">';
    $cache_test .= '<p>Cache is not Writable, seeing what I can do</p>';
    $cache_test .= '</div>';
} else {
    $cache_test = '<div id="initilise" class="error_ok">';
    $cache_test .= '<p>Cache is Ok, Loading...</p>';
    $cache_test .= '</div>';
}

include(__DIR__ . '/template.phtml');
