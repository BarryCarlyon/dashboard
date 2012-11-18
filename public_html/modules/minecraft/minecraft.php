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

//while(!$data['Details']){
		$data = $query->GetData();
echo 'here:';print_r($data);
//}
		// stop the blanking
		if (isset($data['Details'])) {
			$fp = fopen(DASHBOARD_CACHE_PATH . 'minecraft.json', 'w');
			fwrite($fp, json_encode($data));
			fclose($fp);
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
//		$html = '<pre>' . print_r($data,true) . '</pre>';
		$html = '<table style="width: 100%;">';
		$html .= '<tr><td>HostName</td><td>' . $data['Details']['hostname'] . '</td></tr>';
		$html .= '<tr><td>Version</td><td>' . $data['Details']['version'] . '</td></tr>';
		$html .= '<tr><td>Players</td><td>' . $data['Details']['numplayers'] . '/' . $data['Details']['maxplayers'] . '</td></tr>';
		$html .= '<tr><td></td><td><ul>';
		foreach ($data['Players'] as $player) {
			$html .= '<li>' . $player['player'] . '</li>';
		}
		$html .= '</ul></td></tr>';
//		$html .= '<tr><td colspan="2">' . date('r',time()) . '</td></tr>';
		$html .= '</table>';
		return $html;
	}
}
