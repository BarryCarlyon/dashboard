<?php

class facebookpushstatusModule extends module {
	public $id = 'facebookpushstatus';
	public $title = 'Facebook Push Status';

	public $schedule = false;
	public $refresh = true;

	public $width = 2;
	public $height = 1;

	private $url = 'https://www.facebook.com/feeds/api_status.php';

	public function content()
	{
		// pacific time
		$data = $this->cache($this->url, 3600);
		if ($data) {
			$data = json_decode($data);
			if (json_last_error() == JSON_ERROR_NONE) {
				$html = '<table style="width: 100%;">';
				foreach ($data as $event => $info) {
					$html .= '<tr><td>' . ucwords($event) . '</td>';
					if ($event == 'push') {
						$html .= '<td title="' . $info->id . '">' 
							. $info->status
							. ' '
							. $info->updated . '</td>';
					} else if ($event == 'current') {
						$html .= '<td>' . $info->subject . '</td>';
					} else {
						$html .= '<td>' . print_r($info, true) . '</td>';
					}
					$html .= '</tr>';
				}
				$html .= '</table>';
				return $html;
			} else {
				return $this->error('JSON Error');
			}
		}
		return $this->error('Error');
	}
}
