<?php

class pingthreeModule extends module {
    public $id = 'pingthree';
    public $title = 'Ping Three';

    public $schedule = '* * * * *';
    public $refresh = true;

    public $width = 2;
    public $height = 1;

    function content($first = false) {
        $data = $this->loadCache('pingthree');
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
        $data = $this->loadCache('pingthree');
        if (!$data) {

        }
        $data = json_decode($data);

        if ($data->lastIPTime != date('H', time())) {
            $exec = 'ssh Paul\ Walker@photography.local "curl http://ipecho.net/plain"';
            exec($exec, $output);
            $data->lastIP = implode($output);
            $data->lastIPTime = date('H', time());
            unset($output);
        }

        $exec = 'ssh Paul\ Walker@photography.local "ping -c 5 google.com"';
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

        $this->cacheData(json_encode($data), 'pingthree');
    }
}
