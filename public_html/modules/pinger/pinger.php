<?php

class pingerModule extends module {
	public $id = 'pinger';
	public $title = 'Pinger';

	public $schedule = '*/5 * * * *';
	public $refresh = true;

	public $width = 1;
	public $height = 1;

	public function cron() {
		// do in order not multi fork
		$urls = array(
			'http://barrycarlyon.co.uk/' => 'BC',
			'http://www.fredaldous.co.uk/' => 'FA',
		);
		$results = array();

		foreach ($urls as $url => $name) {
			// first test that it's there

			
			$start = time();

			$command = 'cd ' . DASHBOARD_TRASH_DIR . ' && wget -q --page-requisites ' . $url;
			echo 'Running ' . $command . "\n";
			exec($command);

			$end = time();

			$diff = $end - $start;

			echo 'Did ' . $url . ' - ' . $diff . "\n";

			$results[$name] = $diff;
		}

		$this->cacheData(json_encode($results), 'pinger');
	}

	public function content() {
		$data = $this->loadCache('pinger');
		$data = json_decode($data);

		$html = '<h4>Pinger</h4>';

		foreach ($data as $word => $time) {
			$html .= $word . ': ' . $time . ' S<br />';
		}

		return $html;

//		return print_r($data,true);
	}
}
