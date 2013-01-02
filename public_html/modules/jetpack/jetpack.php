<?php

class jetpackModule extends module {
		public $id = 'jetpack';
		public $title = 'jetpack RSS';

		public $schedule = '*/5 * * * *';
		public $refresh = true;

		public $width = 3;
		public $height = 2;

		public function cron() {
			return;
		}

		public function content() {
			$urls = array();
			$urls[] = 'feed://wordpress.org/support/rss/plugin/jetpack';

			$html = '';
			require_once(DASHBOARD_LIB_PATH . 'simplepie.inc');

			$feed = new SimplePie();
			$feed->set_feed_url($urls);
			$feed->set_cache_location(DASHBOARD_CACHE_PATH);
			$feed->set_cache_duration(1800);
			$feed->init();
			$feed->handle_content_type();

			$html .= '<h4>Jetpack</h4>';
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
		}
}
