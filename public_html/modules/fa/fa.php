<?php

class faModule extends module {
    public $id = 'fa';
    public $title = 'FA';

    public $schedule = '*/5 * * * *';
    public $refresh = true;

    public $width = 2;
    public $height = 2;

    public function cron() {
        $response = array();

        $status = array(
            'pending',
            'payment_pending',
            'processing',
            'accepted',
            'picking',
            'packing',
            'complete',
            'tracking',
            'delivered',
        );

        include(__DIR__ . '/config.php');

        try {
            $client = new SoapClient($soap_host);
            $sessionId = $client->login($soap_user, $soap_pass);
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            $this->cacheData(json_encode($response), 'fa');
            return;
        }

        $params = array(
            'complex_filter' => array(
                array(
                    'key' => 'status',
                    'value' => array(
                        'key' => 'eq',
                        'value' => '',
                    )
                ),
                array(
                    'key' => 'updated_at',
                    'value' => array(
                        'key' => 'gt',
                        'value' => date('Y-m-d H:i:s', time() - 86400),//'2013-05-14 00:00:00'
                    )
                )
            ),
        );

        foreach ($status as $state) {
            $params['complex_filter'][0]['value']['value'] = $state;

            try {
                $result = $client->salesOrderList($sessionId, $params);
                $total = count($result);

                echo $state . ' ' . $total . "\n";

                $response[$state] = $total;
            } catch (Exception $e) {
                echo 'Failed ' . $state . ' - ' . $e->getMessage() . "\n";
            }
        }
        $response['last'] = time();

        $this->cacheData(json_encode($response), 'fa');
    }

    public function content() {
        $data = $this->loadCache('fa');
        $data = json_decode($data);

        $html = '<h4>Orders</h4>';

        $html .= '<table style="width: 100%;">';
        foreach ($data as $state => $count) {
            if ($state != 'last'){
                $html .= '<tr><td>' . ucwords(str_replace('_', ' ', $state)) . ':</td>
                <td>' . $count . '</td></tr>';
            }
        }
        $html .= '</table>';
        $html .= '<p style="text-align: center">' . date('H:i:s', $data->last) . '</p>';

        return $html;
    }
}
