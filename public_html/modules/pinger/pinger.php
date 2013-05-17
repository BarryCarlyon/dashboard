<?php

class pingerModule extends module {
    public $id = 'pinger';
    public $title = 'Pinger';

    public $schedule = '*/5 * * * *';
    public $refresh = true;

    public $width = 2;
    public $height = 1;

    public $urls = array(
        'http://barrycarlyon.co.uk/' => 'BC',
        'http://www.fredaldous.co.uk/' => 'FA',
        'https://live.sagepay.com/mysagepay/' => 'SagePay',
    );

    public function cron() {
        // do in order not multi fork
        $urls = $this->urls;
        $results = array();

        foreach ($urls as $url => $name) {
            // first test that it's there
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_exec($ch);
            $r = curl_getinfo($ch);
            curl_close($ch);

            $code_start = substr($r['http_code'], 0, 1);
            if (
                $r['http_code'] == 0
                ||
                $code_start == 4
                ||
                $code_start == 5
                ) {
                // request failed
                echo $url . ' Offline' . "\n";
                $results[$name] = 'Offline';
            } else {
                echo $url . ' Online Speed test' . "\n";
                $start = microtime(true);

                $command = 'cd ' . DASHBOARD_TRASH_DIR . ' && wget -q --page-requisites ' . $url;
                echo 'Running ' . $command . "\n";
                exec($command);

                $end = microtime(true);

                $diff = $end - $start;
                $diff = number_format($diff, 6);

                echo 'Did ' . $url . ' - ' . $diff . "\n";

                $results[$name] = $diff;
            }
        }

        $this->cacheData(json_encode($results), 'pinger');
    }

    public function content() {
        $data = $this->loadCache('pinger');
        $data = json_decode($data);

        $html = '<h4>Pinger</h4>';

        foreach ($data as $word => $time) {
            $html .= $word . ': ' . $time . '<br />';
        }

        return $html;
    }

    public function options() {
        $urls = $this->urls;
        $options = array(
            'url' => array(
                'name' => 'Name:Url',
                'type' => 'multiple',
                'keys' => true,
                'values' => $urls,
            )
        );
        return $options;
    }
}
