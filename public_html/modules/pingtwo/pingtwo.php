<?php

class pingtwoModule extends module {
    public $id = 'pingtwo';
    public $title = 'Ping Two';

    public $schedule = '* * * * *';
    public $refresh = true;

    public $width = 2;
    public $height = 1;

    function content($first = false) {
        $data = $this->loadCache('pingtwo');
        $data = json_decode($data);

        $html = '
<table>
    <tr><td>IP:</td><td>' . $data->lastIP . '</td></tr>
    <tr><td>Ping:</td><td>' . $data->average . '</td></tr>
    <tr><td>Time:</td><td>' . date('H:i:s', $data->myping) . '</td></tr>
</table>
';

        return $html;
    }

    function cron() {
        $data = $this->loadCache('pingtwo');
        if (!$data) {

        }
        $data = json_decode($data);

        if ($data->lastIPTime != date('H', time())) {
            $exec = 'ssh barrycarlyon@world-of-web.local "curl http://ipecho.net/plain"';
            exec($exec, $output);
            $data->lastIP = implode($output);
            $data->lastIPTime = date('H', time());
            unset($output);
        }

        $exec = 'ssh barrycarlyon@world-of-web.local "ping -c 5 google.com"';
        exec($exec, $output);

        $items = array();

        foreach ($output as $index => $line) {
            if (!empty($line) && strpos($line, 'bytes from')) {
                $line = strstr($line, 'time=');
                $line = strstr($line, ' ', true);
                $line = str_replace('time=', '', $line);

                $items[] = $line;
            }
        }

        $total = $count = $average = 0;
        foreach ($items as $item) {
            $total += $item;
            $count++;
        }
        $average = $total / $count;

        $data->average = $average;
        $data->myping = time();

        $this->cacheData(json_encode($data), 'pingtwo');
    }
}
