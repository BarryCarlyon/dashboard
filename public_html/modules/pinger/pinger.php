<?php

class pinger extends module {
	public $schedule = '*/5 * * * *';
	public $id = 'pinger';
	public $title = 'Pinger';

	public function cron() {
		echo 'called';
	}
}
