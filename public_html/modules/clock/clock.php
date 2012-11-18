<?php

class clock extends module
{
	public $id = 'clock';
	public $title = 'Clock';
	public $width = 2;
	public $height = 2;

	public function header() {
		$header = '
<script type="text/javascript">
	jQuery(document).ready(function() {
		swfobject.embedSWF("' . DASHBOARD_URL_MODULES . 'clock/tiny.swf", "' . $this->id . '_clock", "200", "200", "9.0.0", "", {}, {wmode: "transparent"});
	});
</script>
';
		return $header;
	}
	public function content() {
		return '<div style="width: 200px; margin-left: auto; margin-right: auto;"><div id="' . $this->id . '_clock"></div></div>';
	}
}
