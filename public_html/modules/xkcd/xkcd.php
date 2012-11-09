<?php

class xkcd extends module {
	public $id = 'xkcd';
	public $title = 'XKCD';

	public function content() {
		$url = 'http://xkcd.com/info.0.json';
		// pacific time
		$data = $this->cache($url);
		if ($data) {
			$data = json_decode($data);
			if (json_last_error() == JSON_ERROR_NONE) {
				$html = '<center>';
				$html .= '<img src="' . $data->img . '" style="max-width: 100%;" />';
				$html .= '<div>' . $data->title . '</div>';
				$html .= '</center>';
				$html .= '<div>' . $data->alt . '</div>';
				return $html;
			} else {
				return $this->error('JSON Error');
			}
		}
		return $this->error('Error');
	}
}
