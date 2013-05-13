<?php

// bump out to make sure we run at 1 second past the minute
sleep(1);

// initiate cron
define('DASHBOARD_CACHE_PATH', __DIR__ . '/cache/');
define('DASHBOARD_MODULES_PATH', __DIR__ . '/public_html/modules/');
define('DASHBOARD_LIB_PATH', __DIR__ . '/lib/');

include(DASHBOARD_LIB_PATH . 'module.class.php');
include(DASHBOARD_LIB_PATH . 'Crontab.class.php');

// trash
define('DASHBOARD_TRASH_DIR', DASHBOARD_CACHE_PATH . 'trash/');
if (is_dir(DASHBOARD_TRASH_DIR)) {
    deltree(DASHBOARD_TRASH_DIR);
}
function delTree($dir) { 
    $files = glob( $dir . '*', GLOB_MARK ); 
    foreach( $files as $file ){ 
        if( substr( $file, -1 ) == '/' ) 
            delTree( $file ); 
        else 
            unlink( $file ); 
    } 
    rmdir( $dir ); 
}
mkdir(DASHBOARD_TRASH_DIR);

$widgets = $enabled_modules = array();
// module load

$state = DASHBOARD_CACHE_PATH . 'state.json';
if (is_file($state)) {
    $data = file_get_contents($state);
    $data = json_decode($data,true);

    $enabled_modules = $data;
}
foreach ($enabled_modules as $module) {
    include(DASHBOARD_MODULES_PATH . $module['name'] . '/' . $module['name'] . '.php');
    $call = $module['name'] . 'Module';
    $inst = new $call();
    if ($inst->schedule) {
        echo 'enabed ' . $call . "\n";
        $widgets[$module['name']] = $inst;
    }
}

$next_min = mktime(date('H', time()), date('i', time()), 0);

echo date('r', time()) . "\n";

$schedule = $pids = array();

// load the schedules
if (is_file(DASHBOARD_CACHE_PATH . 'cronschedule.json')) {
    $schedule = file_get_contents(DASHBOARD_CACHE_PATH . 'cronschedule.json');
    $schedule = json_decode($schedule, true);
    if (!is_array($schedule)) {
        $schedule = array();
    }
}

$force = isset($argv[1]) ? $argv[1] : false;

foreach ($widgets as $name => $widget) {
    if (isset($schedule[$name])) {
        if ($schedule[$name] < time() || $force) {
            // run the task
            do_fork($name);
            // reschedule
            $schedule[$name] = Crontab::parse($widget->schedule, $next_min);
        }
    } else {
        // never run schdule it
        $schedule[$name] = Crontab::parse($widget->schedule, $next_min);
    }
}

// write schedule back to the cache
$fp = fopen(DASHBOARD_CACHE_PATH . 'cronschedule.json', 'w');
fwrite($fp, json_encode($schedule));
fclose($fp);

while (count($pids) > 0) {
    $ended = pcntl_waitpid(-1, $status, WNOHANG);
    foreach ($pids as $key => $pid) {
        if ($pid == $ended) {
            unset($pids[$key]);
            echo "\n" . $ended . ' has ended' . "\n";
            echo count($pids) . ' still running' . "\n";
        }
    }
}

echo "\n" . 'All Done' . "\n";

function do_fork($name) {
    global $pids, $widgets;
    $pid = pcntl_fork();
    if ($pid == -1) {
        echo 'Forking Failed' . "\n";
        return;
    } elseif ($pid) {
        echo 'Forked ' . $name . '-' . $pid ."\n";
        $pids[] = $pid;
    } else {
        $widgets[$name]->cron();
        exit;
    }
}
