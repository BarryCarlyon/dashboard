<?php

class clock extends module
{
	public $id = 'clock';
	public $title = 'Clock';

	public function header() {
		$header = '
<script type="text/javascript">
	jQuery(document).ready(function() {
		swfobject.embedSWF("' . DASHBOARD_URL_MODULES . 'clock/tiny.swf", "' . $this->id . '_content", "200", "200", "9.0.0", "", {}, {wmode: "transparent"});
	});
</script>
';
		return $header;
	}
}
