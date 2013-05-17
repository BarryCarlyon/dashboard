<?php
date_default_timezone_set('UTC');

session_start();

$widgets = array();

include('widgets/clock/clock.php');
include('widgets/ga/ga.php');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-gb" xml:lang="en-gb">
<head>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.9.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.9.1/themes/ui-darkness/jquery-ui.css" />
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js"></script>
    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
    <link rel="stylesheet" type="text/css" href="assets/style.css" />
    <script type="text/javascript" src="assets/script.js"></script>
    <script type="text/javascript">
        google.load('visualization', '1', {packages: ['corechart']});
    </script>
    <script type="text/javascript">
    </script>

    <script type="text/javascript" src="assets/jquery.gridster.min.js"></script>
    <link rel="stylesheet" type="text/css" href="assets/jquery.gridster.min.css" />
</head>
<body>

    <div class="gridster">
        <ul>
            <?php
                echo implode($widgets);
            ?>
        </ul>
    </div>

</body>
</html>