<?php

$do = isset($_GET['do']) ? $_GET['do'] : false;
switch ($do) {
    case 'loadWidget':
        $widget = str_replace('_widget', '', $_GET['widget']);
        $widget = new $widget();
        echo $widget->bodyOnly();
        break;

    case 'toggleWidget':
        $parent = $_GET['parent'];
        $toggle = $_GET['widget'];
        $widgets = json_decode(file_get_contents(DASHBOARD_CACHE_PATH . 'column/' . $parent . '.json'), true);
        foreach ($widgets as &$widget) {
            if ($widget == $toggle) {
                // change it
                $widget = str_replace('_closed', '_open', $widget);
                $widget = str_replace('_widget', '_closed', $widget);
                $widget = str_replace('_open', '_widget', $widget);
            }
        }
        $widgets = json_encode($widgets);
        $fp = fopen(DASHBOARD_CACHE_PATH . 'column/' . $parent . '.json', 'w');
        fwrite($fp, $widgets);
        fclose($fp);
        break;
}
if ($do) {
    exit;
}
