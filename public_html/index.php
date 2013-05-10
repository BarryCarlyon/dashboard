<?php

define('DASHBOARD_CACHE_PATH', __DIR__ . '/../cache/');
define('DASHBOARD_URL_ROOT', str_replace(array('index.php', 'test.php'), '', $_SERVER['SCRIPT_NAME']));
define('DASHBOARD_URL_MODULES', DASHBOARD_URL_ROOT . 'modules/');
define('DASHBOARD_MODULES_PATH', __DIR__ . '/modules/');
define('DASHBOARD_LIB_PATH', __DIR__ . '/../lib/');

include(DASHBOARD_LIB_PATH . 'functions.php');

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
include(__DIR__ . '/../lib/ajax.php');

$enabled_modules = $enabled_modules_pos = array();

$state = DASHBOARD_CACHE_PATH . 'state.json';
if (is_file($state)) {
    $data = file_get_contents($state);
    $data = json_decode($data,true);

    $enabled_modules = $data;
}

/*

$state_items = DASHBOARD_CACHE_PATH . 'state_items.json';
if (is_file($state_items)) {
    $data = file_get_contents($state_items);
    $data = json_decode($data);

    $pos = file_get_contents(DASHBOARD_CACHE_PATH . 'state.json');
    $pos = json_decode($pos, TRUE);

    $enabled_modules = $data;
    $enabled_modules_pos = $pos;
}
*/

// cache test
// write test
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

return;
/*
<div id="widget_source" class="col">
<p class="title">Widgets</p>
<ul class="widgets">
<?php
foreach ($widgets as $base => $class) {
    echo '<li id="' . $class->id . '" data-sizex-open="' . (isset($class->width)? $class->width : 1) . '" data-sizey-open="' . (isset($class->height)? $class->height : 1) . '">' . $class->titleOnly() . '</li>';
}
?>
</ul>
</div>

<div id="loading">cake</div>

</body>
</html>
*/
