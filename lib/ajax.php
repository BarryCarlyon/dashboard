<?php

$do = isset($_GET['do']) ? $_GET['do'] : false;
switch ($do) {
    case 'loadWidget':
        $widget = str_replace('_widget', '', $_GET['widget']);
        $call = $widget . 'Module';
        $widget = new $call();
        if (method_exists($widget, 'ajax')) {
            echo $widget->ajax();
        } else {
            echo $widget->bodyOnly();
        }
        break;

    case 'saveState':
        $data = json_decode($_POST['data']);
        $items = json_decode($_POST['items']);

        $state = array();
        foreach ($data as $index => $dims) {
            $dims->name = $items[$index];
            $state[] = $dims;
        }

        $state = json_encode($state);
//        $state = print_r($data,true);
//        $state .= print_r($items,true);

        $fp = fopen(DASHBOARD_CACHE_PATH . 'state.json', 'w');
        fwrite($fp, $state);
        fclose($fp);
        break;
}
if ($do) {
    exit;
}
