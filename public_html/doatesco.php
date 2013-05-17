<?php

$result = '';
if ($_POST) {
    ob_start();
    date_default_timezone_set('Europe/London');

include('/Users/barrycarlyon/scripts/mssql/config.php');
include('/Users/barrycarlyon/scripts/mssql/common.php');
$mssql = new mssql($mssql_database);

    $work_id = $_POST['order_id'];

    if (is_numeric($work_id)) {
        $query = 'SELECT referenceNumber FROM [Order] WHERE ID = ' . $work_id;
        $result = $mssql->query($query);

        if (mssql_num_rows($result)) {
            $row = $mssql->row($result);

            $ref = $row['referenceNumber'];

            if (FALSE !== stripos($ref, 'TescoPU')) {
                $order_id = strstr($ref, 'TescoPU');
                $order_id = strstr($order_id, ' ');
                $mag_id = substr($order_id, 1);

                echo 'Running Magento ID: ' . $mag_id;

//                echo '<pre>';

            /*
                echo '<br />';
                $exec = 'cd /Users/barrycarlyon/scripts/mssql/commands/ && php doatesco.php ' . $mag_id;
                echo $exec;
                echo '<br />';
                    exec($exec, $return);
                    print_r($return);

                $exec = 'ssh -p 2020 fredaldo@fredaldous.co.uk "cd public_html/shell/ && php repair.php ' . $mag_id . '"';
                exec($exec, $r, $return);
                print_r($r);
                print_r($return);
            */

//                echo 'One Moment!';
//                echo "\n";
                if (touch(__DIR__ . '/../do/' . $mag_id)) {
                    echo 'Arriving';
                } else {
                    echo 'No Failed';
                }

//                echo '</pre>';
            } else {
                echo 'Not Click and Collect!';
            }
        } else {
            echo 'THIS AINT A WORK ORDER!';
        }
    } else {
        echo 'WHY OH WHY DID YOU PUT WORDS IN?';
    }

    $result = ob_get_contents();
    ob_end_clean();

    if (isset($_POST['stop'])) {
        echo $result;
        exit;        
    }
}

?>
<html>
<head>
    <title>Tesco Label</title>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <script type="text/javascript">
jQuery(document).ready(function() {
    jQuery('#order_id').focus();

    jQuery(window).focus(function() {
        jQuery('#order_id').focus();
    });
});
    </script>
</head>
<bdoy>
<?php

echo $result;

?>
<br />
<form action="" method="post">
    <fieldset>
        <legend>Print a Tesco Order Label</legend>
        Scan Work Order >
        <input type="text" name="order_id" id="order_id" />
        <input type="submit" value="Print" />
    </fieldset>
</form>
</body>
</html>
