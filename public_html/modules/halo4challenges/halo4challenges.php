<?php

class halo4challengesModule extends module
{
	public $id = 'halo4challenges';
	public $title = 'Halo 4 Challenges';
	public $width = 2;
	public $height = 3;

	public function content() {
		$url = 'http://halocharts.com/2012/json_challenges.php';
		// pacific time
		$data = $this->cache($url);
		if ($data) {
			$data = json_decode($data);
			if (json_last_error() == JSON_ERROR_NONE) {
				$html = '';
				foreach ($data as $period_name => $challenges) {
					$html .= '<h3>' . $period_name . '</h3><ul>';
					foreach ($challenges as $challenge) {
						$html .= '<li>';
						$html .= $challenge->ChallengeType . ': ' . $challenge->ChallengeName;
						$html .= '<br />';
						$html .= $challenge->ChallengeDescription;
						// xp emblem day
						$html .= '</li>';
					}
					$html .= '</ul>';
				}
				return $html;
			} else {
				return $this->error('JSON Error');
			}
		}
		return $this->error('Error');
	}
}
