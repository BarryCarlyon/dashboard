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
        include($path . '/' . $base . '.php');
        $widgets[$base] = new $base();
    }
}

// run ajax
include(__DIR__ . '/../lib/ajax.php');

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-gb" xml:lang="en-gb">
<head>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.9.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.9.1/themes/ui-darkness/jquery-ui.css" />
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js"></script>
    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
    <link rel="stylesheet" type="text/css" href="assets/style.css" />
    <script type="text/javascript" src="assets/script.js"></script>
    <script type="text/javascript">
        google.load('visualization', '1', {packages: ['corechart']});
    </script>
    <script type="text/javascript">
jQuery(document).ready(function() {
<?php
foreach ($widgets as $name => $widget) {
    if (isset($widget->refresh) && $widget->refresh) {
        echo 'registerRefresh(\'' . $name . '\')' . "\n";
    }
}
?>
});
</script>

<script type="text/javascript" src="assets/jquery.gridster.min.js"></script>
<link rel="stylesheet" type="text/css" href="assets/jquery.gridster.min.css" />

</head>
<body>
<div class="gridster"><ul></ul></div>

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