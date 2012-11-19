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

//		$query->SetIpPort("216.189.8.183:2302");
		$query->SetIpPort("barrycarlyon.co.uk:25565");
//		$query->SetIpPort("play.phantomcraft.net:25565");

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
			// blanked
			// switch to alt method

			// create
			$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if ($socket === false) {
				echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
				// system fail
				exit;
			}

			// connect
			$result = socket_connect($socket, $server, $port);
			if ($result === false) {
				echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
				// server DOWN
				exit;
			}

			// Server Ip Load data

			$in = "\xfe";
			$out = '';

			// send ping
			$result = socket_write($socket, $in, strlen($in));
			if ($result === false) {
				echo "socket_write() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
				// server DOWN
				exit;
			}

			// read data
			$string = '';
			while ($out = @socket_read($socket, 2048)) {
				$string .= $out;
			}
			if ($out === FALSE) {
				echo "socket_read() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
				// server DOWN
				exit;
			}
			$data = explode("\xa7", $string);

			$existing = json_decode(file_get_contents(DASHBOARD_CACHE_PATH . 'minecraft.json'), TRUE);
			$existing['Details']['numplayers'] = $data[1];
/*
			$server_description = $data[0];
			$players_on = $data[1];
			$players_max = $data[2];
*/

			$fp = fopen(DASHBOARD_CACHE_PATH . 'minecraft.json', 'w');
			fwrite($fp, json_encode($existing));
			fclose($fp);

			// close the connection
			$in = "\xff";

			socket_write($socket, $in, strlen($in));

			socket_close($socket);

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
		$html .= '<tr><td>HostName</td><td>' . $data['Details']['hostname'] . '</td></tr>';
		$html .= '<tr><td>Version</td><td>' . $data['Details']['version'] . '</td></tr>';
		$html .= '<tr><td>Players</td><td>' . $data['Details']['numplayers'] . '/' . $data['Details']['maxplayers'] . '</td></tr>';
		$html .= '<tr><td></td><td><ul>';
		foreach ($data['Players'] as $player) {
			$html .= '<li>' . $player['player'] . '</li>';
		}
		$html .= '</ul></td></tr>';
		$html .= '</table>';
		return $html;
	}
}
