<?php

include(__DIR__ . '/../lib/functions.php');

define('DASHBOARD_CACHE_PATH', __DIR__ . '/../cache/');
define('DASHBOARD_URL_ROOT', $_SERVER['REQUEST_URI']);
define('DASHBOARD_URL_MODULES', $_SERVER['REQUEST_URI'] . 'modules/');

include(__DIR__ . '/../lib/ajax.php');

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-gb" xml:lang="en-gb">
<head>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/jquery-ui.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/themes/ui-darkness/jquery-ui.css" />
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js"></script>
    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
    <link rel="stylesheet" type="text/css" href="style.css" />
    <script type="text/javascript" src="script.js"></script>
    <script type="text/javascript">
        google.load('visualization', '1', {packages: ['corechart']});
    </script>
<?php

$widgets = array();
$body = '';
// module load
$dir = new FilesystemIterator(__DIR__ . '/modules/');
foreach ($dir as $path => $fileinfo) {
    if ($fileinfo->isDir()) {
        $base = basename($path);
        include($path . '/' . $base . '.php');
        $widgets[$base] = $base::title;
//        $widget = new $base();
//        $body .= $widget->generate();
    }
}

?>
</head>
<body>
<div id="controller">
    Control
    <ul>
        <li class="create_column">Create Column</li>
    </ul>
</div>
<?php
//echo $body;
if (!is_file(DASHBOARD_CACHE_PATH . 'columns.json')) {
    $columns = array();
} else {
    $columns = json_decode(file_get_contents(DASHBOARD_CACHE_PATH . 'columns.json'), TRUE);
}
if (is_array($columns)) {
    foreach ($columns as $col) {
        echo columnRender($col[0], $col[1]);
    }
}
?>
<div id="widget_source" class="col">
<p>Widgets</p>
<div class="widgets">
<?php

for ($x=0;$x<10;$x++) {
    echo $x;
    echo '<br />';
}

?>
</div>
</div>
</body>
</html>