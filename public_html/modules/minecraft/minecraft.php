<?php

class minecraft extends module
{
	public $schedule = '*/5 * * * *';
	public $id = 'minecraft';
	public $title = 'Minecraft';
	public $refresh = true;
	public $width = 2;
	public $height = 1;

	public function cron() {
		echo 'Run Minecraft';
		define("DEBUG", TRUE);
		include(DASHBOARD_MODULES_PATH . 'minecraft/gsquery.php');

		$query = new GSQuery();
		$query->SetProtocol("GameSpy4");

//		$server = 'barrycarlyon.co.uk';
		$server = 'play.phantomcraft.net';
		$port = '25565';

		$query->SetIpPort($server .':' . $port);

		$query->SetRequestData(array("FullInfo"));
		$query->SetSocketTimeOut(0, 100000);
		$query->SetSocketLoopTimeOut(5000);

		$data = $query->GetData();

		// stop the blanking
		if (isset($data['Details'])) {
			$fp = fopen(DASHBOARD_CACHE_PATH . 'minecraft.json', 'w');
			fwrite($fp, json_encode($data));
			fclose($fp);
		} else {
			echo 'fail back';

			$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); //Create the socket
			$connected = socket_connect($socket, $server, $port); //Try and connect using the info provided above
			 
			if (!$connected)
				return;
			 
			socket_send($socket, "\xFE\x01", 2, 0); //Send the server list ping request (two bytes)
			$retVal = socket_recv($socket, $data, 1024, 0); //Get the info and store it in $data
			socket_close($socket); //Close socket
			 
			if ($retVal != false && substr($data, 0, 1) == "\xFF") //Ensure we're getting a kick message as expected
			{
			    $data = substr($data, 9); //Remove packet, length and starting characters
			    $data = explode("\x00\x00", $data); //0000 separated info

				$existing = json_decode(file_get_contents(DASHBOARD_CACHE_PATH . 'minecraft.json'), TRUE);
				$existing['Details']['version'] = $data[1];
				$existing['Details']['hostname'] = $data[2];
				$existing['Details']['numplayers'] = $data[3];
				$existing['Details']['maxplayers'] = $data[4];


				$fp = fopen(DASHBOARD_CACHE_PATH . 'minecraft.json', 'w');
				fwrite($fp, json_encode($existing));
				fclose($fp);
			}
		}
	}

	public function content() {
		$data = array();
		if (is_file(DASHBOARD_CACHE_PATH . 'minecraft.json')) {
			$data = file_get_contents(DASHBOARD_CACHE_PATH . 'minecraft.json');
			$data = json_decode($data, TRUE);
			if (!is_array($data)) {
				$data = array();
			}
		}
		// process
		$html = '<table style="width: 100%;">';
		$html .= '<tr><td colspan="2">' . $data['Details']['hostname'] . '</td></tr>';
		if (isset($data['Details']['version'])) {
			$html .= '<tr><td>Version</td><td>' . $data['Details']['version'] . '</td></tr>';
		}
		$html .= '<tr><td>Players</td><td>' . $data['Details']['numplayers'] . '/' . $data['Details']['maxplayers'] . '</td></tr>';
		if (isset($data['Players'])) {
			$html .= '<tr><td></td><td><ul>';
			foreach ($data['Players'] as $player) {
				$html .= '<li>' . $player['player'] . '</li>';
			}
			$html .= '</ul></td></tr>';
		}
		$html .= '</table>';
		return $html;
	}
}
