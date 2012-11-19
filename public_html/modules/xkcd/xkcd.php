<?php

class xkcd extends module {
	public $schedule = '0 9 * * *';
	public $id = 'xkcd';
	public $title = 'XKCD';
	public $width = 3;
	public $height = 3;

	private $url = 'http://xkcd.com/info.0.json';

	public function cron() {
		$this->cache($this->url);
	}

	public function content() {
		$html = '';

		$data = $this->cache($this->url);
		if ($data) {
			$data = json_decode($data);
			if (json_last_error() == JSON_ERROR_NONE) {
				$html .= '<center>';
				$html .= '<img src="' . $data->img . '" style="max-width: 100%; max-height: 300px;" />';
				$html .= '<div>' . $data->title . '</div>';
				$html .= '</center>';
				$html .= '<div>' . $data->alt . '</div>';
			} else {
				$html .= $this->error('JSON Error');
			}
		}
		require_once(DASHBOARD_LIB_PATH . 'simplepie.inc');
		$url = 'http://what-if.xkcd.com/feed.atom';
		$feed = new SimplePie();
		$feed->set_feed_url($url);
		$feed->set_cache_location(DASHBOARD_CACHE_PATH);
		$feed->init();
		$feed->handle_content_type();

		$html .= '<h4>WhatIf?</h4>';
		$html .= '<ul>';
		foreach ($feed->get_items() as $item) {
			$html .= '<li><a href="' . $item->get_permalink() . '" target="xkcd">' . $item->get_title() . '</a></li>';
		}
		$html .= '</ul>';
		return $html;
	}
}
