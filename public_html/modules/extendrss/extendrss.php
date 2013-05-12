<?php

class extendrssModule extends XmlModule {
	public $id = 'extendrss';
	public $title = 'Extend RSS';

	public $schedule = '*/5 * * * *';
	public $refresh = true;

	public $width = 3;
	public $height = 2;

	protected $urls = array(
		'feed://wordpress.org/support/rss/plugin/jetpack-extras',
		'feed://wordpress.org/support/rss/plugin/wp-zombaio',
	);

	public function cron() {
		return;
	}

	protected function parse($feed) {
		$html = '<h4>Extend</h4>';

		if ($feed) {
			$html .= '<ul>';

			$updated = array();
			foreach ($feed->get_items() as $item) {
				$title = strstr($item->get_title(), '"');
				$time = $item->get_date('U');

				if (isset($updated[$title])) {
					if ($updated[$title][0] < $time) {
						$updated[$title] = array($time, $item, 0);
					} else {
						$updated[$title][2] ++;
					}
				} else {
					$updated[$title] = array($time, $item, 0);
				}
			}
			foreach ($updated as $update) {
				$item = $update[1];
				$html .= '<li><a href="' . $item->get_permalink() . '" target="extendrss" '
					. 'style="display:block;background:#909090;border:1px solid #949494;margin-bottom:2px;padding:3px;text-align:justify;"'
					. '>';
				$html .= $item->get_date() . ' ';
				$html .= $item->get_title() . ' (' . $update[2] . ')</a></li>';
			}

			$html .= '</ul>';

			return $html;
		} else {
			return $html . $this->error('No Feed Items');
		}
	}
}
