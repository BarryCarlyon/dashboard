<?php

class pingerModule extends module {
	public $id = 'pinger';
	public $title = 'Pinger';

	public $schedule = '*/5 * * * *';
	public $refresh = false;

	public $width = 1;
	public $height = 1;

	public function cron() {
		echo 'called';
	}
}
