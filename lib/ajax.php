<?php

$do = isset($_REQUEST['do']) ? $_REQUEST['do'] : false;
if ($do) {
    switch ($do) {
        case 'loadWidget':
            $widget = str_replace('_widget', '', $_REQUEST['widget']);
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

            $fp = fopen(DASHBOARD_CACHE_PATH . 'state.json', 'w');
            fwrite($fp, $state);
            fclose($fp);
            break;

        case 'loadoptions':
            $widget = $_REQUEST['widget'];

            if (is_file(DASHBOARD_MODULES_PATH . $widget . '/' . $widget . '.php')) {
                $widget .= 'Module';
                $module = new $widget();
                if (method_exists($module, 'options')) {
                    echo $module->editControls();
                } else {
                    echo 'This Module has no Settings';
                }
            } else {
                echo 'Well?';
            }

            break;

        case 'updateoptions':
            $widget = $_REQUEST['widget'];

            if (is_file(DASHBOARD_MODULES_PATH . $widget . '/' . $widget . '.php')) {
                $widget .= 'Module';
                $module = new $widget();
                if (method_exists($module, 'options')) {
                    $module->updateSettings($_POST);
                } else {
                    echo 'This Module has no Settings';
                }
            } else {
                echo 'Well?';
            }
            break;
    }
    exit;
}
