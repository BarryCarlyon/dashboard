<?php

header('Content-Type: text');
// bump out to make sure we run at 1 second past the minute
sleep(1);

// initiate cron
define('DASHBOARD_CACHE_PATH', __DIR__ . '/cache/');
define('DASHBOARD_MODULES_PATH', __DIR__ . '/public_html/modules/');
define('DASHBOARD_LIB_PATH', __DIR__ . '/lib/');

include(DASHBOARD_LIB_PATH . 'functions.php');
include(DASHBOARD_LIB_PATH . 'Crontab.class.php');

$widgets = array();
// module load
$dir = new FilesystemIterator(DASHBOARD_MODULES_PATH);
foreach ($dir as $path => $fileinfo) {
    if ($fileinfo->isDir()) {
        $base = basename($path);
        if (is_file($path . '/' . $base . '.php')) {
            include($path . '/' . $base . '.php');
            if (method_exists($base, 'cron')) {
                echo 'found ' . $base . "\n";
                $call = $base . 'Module';
                $widgets[$base] = new $call();
            }
        }
    }
}

$next_min = mktime(date('H', time()), date('i', time()), 0);

//echo '<pre>';
echo date('r', time()) . "\n";

// load the schedule
$schedule = array();
if (is_file(DASHBOARD_CACHE_PATH . 'cronschedule.json')) {
    $schedule = file_get_contents(DASHBOARD_CACHE_PATH . 'cronschedule.json');
    $schedule = json_decode($schedule, true);
    if (!is_array($schedule)) {
        $schedule = array();
    }
}

//print_r($schedule);
$pids = array();

foreach ($widgets as $name => $widget) {
    if (isset($schedule[$name])) {
        if ($schedule[$name] < time()) {
            // run the task
            // FORK
            do_fork($name);
            // reschedule
            $schedule[$name] = Crontab::parse($widget->schedule, $next_min);
        }
    } else {
        // never run schdule it
        $schedule[$name] = Crontab::parse($widget->schedule, $next_min);
    }
}

$fp = fopen(DASHBOARD_CACHE_PATH . 'cronschedule.json', 'w');
fwrite($fp, json_encode($schedule));
fclose($fp);

//print_r($schedule);
//print_r($widgets);

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
        echo 'fork failed' . "\n";
        return;
    } elseif ($pid) {
        echo 'forked ' . $name . '-' . $pid ."\n";
        $pids[] = $pid;
    } else {
        $widgets[$name]->cron();
        exit;
    }
}
