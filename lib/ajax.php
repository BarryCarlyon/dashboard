<?php

$do = isset($_GET['do']) ? $_GET['do'] : false;
switch ($do) {
    case 'loadWidget':
        $widget = str_replace('_widget', '', $_GET['widget']);
        $widget = new $widget();
        echo $widget->bodyOnly();
        break;

    case 'saveState':
        $data = $_POST['data'];
        $fp = fopen(DASHBOARD_CACHE_PATH . 'state.json', 'w');
        fwrite($fp, $data);
        fclose($fp);
        $items = $_POST['items'];
        $fp = fopen(DASHBOARD_CACHE_PATH . 'state_items.json', 'w');
        fwrite($fp, $items);
        fclose($fp);
        break;
}
if ($do) {
    exit;
}
