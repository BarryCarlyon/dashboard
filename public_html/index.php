<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-gb" xml:lang="en-gb">
<head>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/jquery-ui.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/themes/ui-darkness/jquery-ui.css" />
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js"></script>
	<script type="text/javascript" src="http://www.google.com/jsapi"></script>
	<link rel="stylesheet" type="text/css" href="style.css" />
	<script type="text/javascript">
		google.load('visualization', '1', {packages: ['corechart']});


		$(document).ready(function() {
			$('.module .ui-widget-header').prepend( "<span class='ui-icon ui-icon-minusthick'></span>")
			$('.col').sortable({
				connectWith:	'.col',
				items:		'.module',
				placeholder:	'ui-state-highlight',
				containment:	'#content',
				grid:		[25, 25]
			});
			$('.module .ui-icon').click(function() {
				$(this).toggleClass('ui-icon-minusthick').toggleClass('ui-icon-plusthick');
				$(this).parents('.module').find('.ui-widget-content').toggle();
			});
		});
	</script>
<?php

define('DASHBOARD_URL_ROOT', $_SERVER['REQUEST_URI']);
define('DASHBOARD_URL_MODULES', $_SERVER['REQUEST_URI'] . 'modules/');

include(__DIR__ . '/../lib/functions.php');

$body = '';
// module load
$dir = new FilesystemIterator(__DIR__ . '/modules/');
foreach ($dir as $path => $fileinfo) {
	if ($fileinfo->isDir()) {
		$base = basename($path);
		include($path . '/' . $base . '.php');
		$widget = new $base();
		$body .= $widget->generate();
	}
}

?>
</head>
<body>
<?php echo $body; ?>
</body>
</html>