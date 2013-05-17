<?php

$method = isset($_REQUEST['method']) ? $_REQUEST['method'] : false;
if ($method) {

/*
	$data = array(
		time(),
		rand(10, 30),
		rand(5, 15),
		rand(0, 20)
	);
	echo json_encode($data);
*/
	echo file_get_contents('/Users/barrycarlyon/scripts/mssql/commands/cache');
	exit;

}

?>
<html>
<head>

<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">

google.load('visualization', '1.0', {'packages':['corechart']});
google.setOnLoadCallback(drawChart);

<?php

$data = file_get_contents('/Users/barrycarlyon/scripts/mssql/commands/cache');
$data = json_decode($data, true);

$header = 'var header = [';
$graph = 'var graph = [[';
foreach ($data as $h => $initial) {
	$header .= '\'' . $h . '\', ';
	$graph .= $initial . ', ';
}
$header = substr($header, 0, -2);
$header .= '];';
$graph = substr($graph, 0, -2);
$graph .= ']];';

echo $header . "\n" . $graph . "\n";

?>
graph.unshift(header);

//var header = ['Time', 'Web', 'eBay', 'Amazon', 'Play', 'Tesco'];
//var graph = [
//	['Time', 'Web', 'eBay', 'Amazon', 'Play', 'Tesco'],
//	[<?php echo time(); ?>, 0, 0, 0, 0, 0]
//];

function drawChart() {
//	console.log(graph);

	var data = google.visualization.arrayToDataTable(graph);

	var options = {
		isStacked: true
	};

	var chart = new google.visualization.AreaChart(document.getElementById('chart_div'));
	chart.draw(data, options);

	setTimeout('loadstuff()', 1500);
}

var lasttime = <?php echo $data['Time']; ?>;
function loadstuff() {
	jQuery.ajax({
		url: 'graph.php',
		data: {
			method: 'bacon',
		},
		success: function(data) {
//			var line = data;
			var line = new Array();
			for (x=0;x<header.length;x++) {
				line.push(data[header[x]]);
			}
/*
			var line = new Array(
				data[0],
				data[1],
				data[2],
				data[3],
				data[4],
				data[5]
			);*/

//			if (line[0] != )
//console.log(lasttime + ' - ' + line[0]);
			if (lasttime == line[0]) {
				drawChart();
				return;
			}
			lasttime = line[0];

			graph.push(line);
			if (graph.length>20) {
				graph.shift();
				graph.shift();
				graph.unshift(header);
/*
			} else if (graph.length == 3) {
				graph.shift();
				graph.shift();
				graph.push(line);
				graph.unshift(header);
//				graph[1] = line;
*/
			}

			console.log(graph);
			drawChart();
		},
		dataType: 'json'
	})
}

</script>

</head>
<body>

<div id="chart_div"></div>

</body>
</html>