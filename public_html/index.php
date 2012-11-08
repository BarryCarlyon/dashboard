<?php

include(__DIR__ . '/../lib/functions.php');

define('DASHBOARD_CACHE_PATH', __DIR__ . '/../cache/');
define('DASHBOARD_URL_ROOT', $_SERVER['REQUEST_URI']);
define('DASHBOARD_URL_MODULES', $_SERVER['REQUEST_URI'] . 'modules/');

$do = isset($_GET['do']) ? $_GET['do'] : false;
switch ($do) {
    case 'addColumn':
        $col = array(
            '',
            $_GET['width']
        );
        if (!is_file(DASHBOARD_CACHE_PATH . 'columns.json')) {
            $columns = array();
        } else {
            $columns = json_decode(file_get_contents(DASHBOARD_CACHE_PATH . 'columns.json'));
            if (!is_array($columns)) {
                $columns = array();
            }
        }
        $col[0] = 'col_' . (count($columns) + 1);
        $columns[] = $col;
        $columns = json_encode($columns);
        $fp = fopen(DASHBOARD_CACHE_PATH . 'columns.json', 'w');
        fwrite($fp, $columns);
        fclose($fp);
        echo columnRender($col[0], $_GET['width']);
        break;
    case 'changeColumn':
        $col = array(
            $_GET['name'],
            $_GET['width']
        );
        if (!is_file(DASHBOARD_CACHE_PATH . 'columns.json')) {
            $columns = array();
        } else {
            $columns = json_decode(file_get_contents(DASHBOARD_CACHE_PATH . 'columns.json'));
            if (!is_array($columns)) {
                $columns = array();
            }
        }
        foreach ($columns as &$column) {
            if ($column[0] == $col[0]) {
                $column = $col;
            }
        }
        $columns = json_encode($columns);
        $fp = fopen(DASHBOARD_CACHE_PATH . 'columns.json', 'w');
        fwrite($fp, $columns);
        fclose($fp);
        $render = columnRender($_GET['name'], $_GET['width']);
        echo 'jQuery(\'#' . $_GET['name'] . '\').replaceWith(\'' . $render . '\')';
        break;
    case 'deleteColumn':
        $col = $_GET['name'];
        if (!is_file(DASHBOARD_CACHE_PATH . 'columns.json')) {
            $columns = array();
        } else {
            $columns = json_decode(file_get_contents(DASHBOARD_CACHE_PATH . 'columns.json'));
            if (!is_array($columns)) {
                $columns = array();
            }
        }
        foreach ($columns as $index =>$column) {
            if ($column[0] == $col) {
                unset($columns[$index]);
            }
        }
        $columns = json_encode($columns);
        $fp = fopen(DASHBOARD_CACHE_PATH . 'columns.json', 'w');
        fwrite($fp, $columns);
        fclose($fp);
        echo 'jQuery(\'#' . $_GET['name'] . '\').slideUp(function() {jQuery(this).remove()});';
        break;
}
if ($do) {
    exit;
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-gb" xml:lang="en-gb">
<head>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
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

$body = '';
// module load
$dir = new FilesystemIterator(__DIR__ . '/modules/');
foreach ($dir as $path => $fileinfo) {
    if ($fileinfo->isDir()) {
        $base = basename($path);
        include($path . '/' . $base . '.php');
        $widget = new $base();
        $body .= $widget->generate();
    }
}

?>
</head>
<body>
<ul id="controller">
    <li class="create_column">Create Column</li>
</ul>
<?php
//echo $body;
if (!is_file(DASHBOARD_CACHE_PATH . 'columns.json')) {
    $columns = array();
} else {
    $columns = json_decode(file_get_contents(DASHBOARD_CACHE_PATH . 'columns.json'));
}
if (is_array($columns)) {
    foreach ($columns as $col) {
        echo columnRender($col[0], $col[1]);
    }
}
?>
</body>
</html>