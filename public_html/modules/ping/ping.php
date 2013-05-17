<?php

class pingModule extends module {
    public $id = 'ping';
    public $title = 'Ping';

    public $schedule = '* * * * *';
    public $refresh = true;

    public $width = 2;
    public $height = 1;

    function content($first = false) {
        $data = $this->loadCache('ping');
        $data = json_decode($data);

        $html = '
Ping Local
<table>
    <tr><td>IP:</td><td>' . $data->lastIP . '</td></tr>
    <tr><td>Ping:</td><td>' . $data->average . '</td></tr>
    <tr><td>Time:</td><td>' . date('H:i:s', $data->myping) . '</td></tr>
</table>
';

        return $html;
    }

    function cron() {
        $data = $this->loadCache('ping');
        if (!$data) {

        }
        $data = json_decode($data);

        if ($data->lastIPTime != date('H', time())) {
            $ch = curl_init('http://ipecho.net/plain');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $data->lastIP = curl_exec($ch);
            curl_close($ch);
            $data->lastIPTime = date('H', time());
        }

        $exec = '/sbin/ping -c 5 google.com';
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

        $this->cacheData(json_encode($data), 'ping');
    }
}
