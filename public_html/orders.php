<?php

date_default_timezone_set('Europe/London');

$operation = isset($_REQUEST['operation']) ? $_REQUEST['operation'] : false;
if ($operation) {
    header('Content-Type: application/json');

    $response = array(
        'ok' => false,
        'error' => '',
    );

    switch ($operation) {
        case 'comment':
            $newcomment = isset($_POST['comment']) ? $_POST['comment'] : false;
            $id = isset($_POST['id']) ? $_POST['id'] : false;
            if ($newcomment !== false && $id !== false) {
                include('/Users/barrycarlyon/scripts/mssql/common.php');

                $mssql = new mssql();

                $query = 'UPDATE [Order] SET Comment = \'' . $newcomment . '\', LastUpdated = getdate() WHERE ID = ' . $id;
                $mssql->query($query);
                $query = 'SELECT Comment FROM [Order] WHERE ID = ' . $id;
                $result = $mssql->query($query);
                $row = $mssql->row($result);
                if ($row['Comment'] == $newcomment) {
                    $response['ok'] = true;
                } else {
                    $response['error'] = '';
                }
            }
    }

    echo json_encode($response);

    exit;
}


$table = isset($_REQUEST['table']) ? $_REQUEST['table'] : false;

if (!$table) {
?>
<html>
<head>
    <title>Work Orders</title>

    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript">
var locked = false;
var reloader = false;
var hideStatusTimeout = false;
jQuery(document).ready(function() {
    jQuery('#loading').hide();
    jQuery('#status').hide();
    jQuery('#play').hide();
    reloader = setInterval('reloadTable()', 5000);

    jQuery('#pause').click(function(e) {
        e.preventDefault();
        jQuery(this).hide();
        jQuery('#play').show();
        jQuery('#loading').html('Paused').show();
        clearTimeout(reloader);
    });
    jQuery('#play').click(function(e) {
        e.preventDefault();
        jQuery(this).hide();
        jQuery('#pause').show();
        jQuery('#loading').html('Loading').show();
        reloader = setInterval('reloadTable()', 5000);
        reloadTable();
    });

    jQuery('#data').on('click', '.comment', function() {
        var id = jQuery(this).parents('tr').find('.id').html();
        var newcomment = prompt('New Comment for ' + id, jQuery(this).html());
        if (newcomment != null) {
            clearTimeout(hideStatusTimeout);
            jQuery.ajax({
                url: 'orders.php',
                type: 'POST',
                data: {
                    operation: 'comment',
                    id: id,
                    comment: newcomment
                },
                success: function(data) {
                    if (data.ok) {
                        jQuery('#status').html('Comment Updated').fadeIn(function() {
                            hideStatusTimeout = setTimeout('hideStatus()', 5000);
                        });
                    } else {
                        jQuery('#status').html('Comment Failed to Update: ' + data.error).fadeIn(function() {
                            hideStatusTimeout = setTimeout('hideStatus()', 5000);
                        });
                    }
                },
                dataType: 'json',
                context: jQuery(this)
            });
        }
    })
});
function reloadTable() {
    if (locked) {
        return;
    }
    locked = true;
    jQuery('#loading').show();
    jQuery('#data').load('orders.php?table=' + Date.now(), function() {
        jQuery('#loading').hide();
        locked = false;
    });
}
function hideStatus() {
    jQuery('#status').fadeOut();
}
</script>

<link type="text/css" rel="stylesheet" href="styles.css" />

</head>
<body>

<div id="loading">Loading</div>
<div id="status"></div>
<div id="controls">
    <a href="#nowhere" id="pause">Pause</a>
    <a href="#nowhere" id="play">Play</a>
</div>

<div id="data">

<?php
}
?>
<table style="width:100%">

<?php

include('/Users/barrycarlyon/scripts/mssql/common.php');

$mssql = new mssql();

$query = 'SELECT * FROM [Order] WHERE Closed = 0 ORDER BY LastUpdated DESC';
$result = $mssql->query($query);

//$then = time() - 86400;
$then = mktime(0,0,0);
$did = false;
$today = date('d', time());

$onehour = time() - 3600;
$oneday = time() - (3600 * 24);
$oneweek = time() - (60 * 60 * 24 * 7);
$twoweek = time() - (60 * 60 * 24 * 14);

$counter = $recent = 0;

$images = array(
    'Web'       => 'www_page.png',
    'eBay'      => 'ebay.png',
    'Amazon'    => 'amazon.ico',
    'Tesco'     => 'tesco.ico',
    'Play'      => 'play.ico',
    'os'        => 'cash_register.png',
);

$odd = true;
$colspan = 9;

echo '<tbody>';
while ($row = $mssql->row($result)) {
//    echo '<pre>';print_r($row);exit;

    $time = strtotime($row['LastUpdated']);

    if ($time < $then && !$did) {
        $did = true;
        echo '<tr><td colspan="' . $colspan . '"><hr /></td></tr>';
        $today = date('d', $time);
    } else if ($today != date('d', $time)) {
        $today = date('d', $time);
        echo '<tr><td colspan="' . $colspan . '"><hr /></td></tr>';
    }

    $color = '';
    if ($time < $twoweek) {
        $color = 'background: #CC0000; color: #FFFFFF';//white on red
    } else if ($time < $oneweek) {
        $color = 'background: #CCCC00;';//yellow
    } else if ($time > $onehour && empty($row['Comment'])) {
        $color = 'background: #00CC00;';//green
    }

    $order_type = orderType($row['ReferenceNumber']);
    $type = '';
    if (array_key_exists($order_type, $images)) {
        $url = $images[$order_type];
        if (!empty($url)) {
            $type = '<img src="/assets/icons/' . $url . '" style="width: 16px;" />';
        } else {
            $type = substr($order_type, 0, 1);
        }
    } else {
        $type = substr($order_type, 0, 1);
    }

    $format = 'H:i:s';
    if ($time < $then) {
//        $format .= ' d/m/Y';
        $format = 'd/m/Y';
    }

    echo '<tr class="' . ($odd ? 'odd' : 'even') . '">';
    $odd = $odd ? false : true;
    echo '<td style="text-align: center; padding: 1px 3px; display: block;">' . $type . '</td>';
    echo '<td style="text-align: center;" class="id">' . $row['ID'] . '</td>';
    echo '<td style="text-align: center; border-left: 1px solid #000000; border-right: 1px solid #000000;">' . date($format, strtotime($row['Time'])) . '</td>';

    // get customer
    $sub_query = 'SELECT LastName, Zip FROM Customer WHERE ID = ' . $row['CustomerID'];
    $sub_result = $mssql->query($sub_query);
    $sub_row = $mssql->row($sub_result);
    echo '<td>' . $sub_row['LastName'] . '</td><td style="border-right: 1px solid #000000;" nowrap="nowrap">' . $sub_row['Zip'] . '</td>';

    if ($order_type == 'Amazon') {
        $row['ReferenceNumber'] = str_replace(' Order No', '', $row['ReferenceNumber']);
    }

    echo '<td style="">' . $row['ReferenceNumber'] . '</td>';
    echo '<td style="" class="comment">' . $row['Comment'] . '</td>';

    echo '<td style="text-align: center; ' . $color . '">' . date($format, $time) . '</td>';
    $color = '';
    if ($row['Total'] >= 40) {
        $color = 'background: orange;';
    }
    echo '<td style="text-align: right; ' . $color . '">&pound;' . number_format($row['Total'], 2) . '</td>';
    echo '</tr>';

    if (!$did) {
        $recent++;
    }
    $counter++;
}
echo '</tbody>';

    echo '<thead>
<tr>
    <td colspan="' . $colspan . '" style="text-align: center;">
        <div style="position: absolute; top: 0px; right: 0px;">' . date('H:i:s', time()) . '</div>
        Total: ' . $counter . ' Open Orders, Recent/Today: ' . $recent . '
    </td>
</tr>
<tr>
    <th colspan="2">ID</th>
    <th>Opened</th>
    <th colspan="2">Customer</th>
    <th>Ref</th>
    <th>Comment (Click to Update)</th>
    <th>Update</th>
    <th>Total</th>
</tr>
</thead>

</table>
';

if (!$table) {
?>

</div>
</body>
</html>
<?php
}
