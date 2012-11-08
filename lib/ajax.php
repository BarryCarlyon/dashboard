<?php

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
            $columns = json_decode(file_get_contents(DASHBOARD_CACHE_PATH . 'columns.json'), TRUE);
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
            $columns = json_decode(file_get_contents(DASHBOARD_CACHE_PATH . 'columns.json'), TRUE);
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
        echo 'jQuery(\'#' . $_GET['name'] . '\').animate({width: ' . $_GET['width'] . '})';
        break;

    case 'deleteColumn':
        $col = $_GET['name'];
        if (!is_file(DASHBOARD_CACHE_PATH . 'columns.json')) {
            $columns = array();
        } else {
            $columns = json_decode(file_get_contents(DASHBOARD_CACHE_PATH . 'columns.json'), TRUE);
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

    // render it
}
if ($do) {
    exit;
}
