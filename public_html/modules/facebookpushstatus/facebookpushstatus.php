<?php

class facebookpushstatusModule extends JsonModule {
	public $id = 'facebookpushstatus';
	public $title = 'Facebook Push Status';

	public $schedule = false;
	public $refresh = true;

	public $width = 2;
	public $height = 1;

	protected $url = 'https://www.facebook.com/feeds/api_status.php';

	protected function parse($data)
	{
		// pacific time
		if ($data) {
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
		}
		return $this->error('Facebook: Error (No Data) cURL');
	}
}
