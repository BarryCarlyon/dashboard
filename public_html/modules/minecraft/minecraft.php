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

//		$query->SetIpPort("216.189.8.183:2302");
		$query->SetIpPort($server .':' . $port);
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


$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); //Create the socket
$connected = socket_connect($socket, $server, $port); //Try and connect using the info provided above
 
if (!$connected)
    die("Could not connect to server."); //No connection could be established
 
socket_send($socket, "\xFE\x01", 2, 0); //Send the server list ping request (two bytes)
$retVal = socket_recv($socket, $data, 1024, 0); //Get the info and store it in $data
socket_close($socket); //Close socket
 
if ($retVal != false && substr($data, 0, 1) == "\xFF") //Ensure we're getting a kick message as expected
{
    $data = substr($data, 9); //Remove packet, length and starting characters
    $data = explode("\x00\x00", $data); //0000 separated info

    print_r($data);
/*
    $protocolVersion = $data[0]; //Get it all into separate variables
    $serverVersion = $data[1];
    $motd = $data[2];
    $playersOnline = $data[3];
    $playersMax = $data[4];
*/

			$existing = json_decode(file_get_contents(DASHBOARD_CACHE_PATH . 'minecraft.json'), TRUE);
			$existing['Details']['version'] = $data[1];
			$existing['Details']['hostname'] = $data[2];
			$existing['Details']['numplayers'] = $data[3];
			$existing['Details']['maxplayers'] = $data[4];


			$fp = fopen(DASHBOARD_CACHE_PATH . 'minecraft.json', 'w');
			fwrite($fp, json_encode($existing));
			fclose($fp);
}
else
{
//    die("Couldn't get expected data"); //Either retVal was false or we didn't get a kick message
}
return;

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

echo '----' . "\n";
echo utf8_decode($string);
echo '----' . "\n";

			$data = explode("\xa7", $string);

			print_r($data);

			$existing = json_decode(file_get_contents(DASHBOARD_CACHE_PATH . 'minecraft.json'), TRUE);
			$existing['Details']['hostname'] = $data[0];
			$existing['Details']['numplayers'] = $data[1];
			$existing['Details']['maxplayers'] = $data[2];
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
