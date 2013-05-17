<?php

class linksModule extends module {
    public $id = 'links';
    public $title = 'Links';

    public $schedule = false;//crontab valid'0 * * * *';
    public $refresh = false;

    public $width = 2;
    public $height = 2;

    function content() {
    	return '
<style type="text/css">
.links a {
    background: #339933;
    margin: 5px;
    padding: 5px;
    display: block;
    text-decoration: none;
    text-align: center;
}
.links a:hover {
    background: #993399;
}
</style>
<ul class="links">
    <li><a href="orders.php">Work Orders</a></li>
    <li><a href="doatesco.php">Preprint a Tesco Order</a></li>
    <li><a href="http://tools.fredaldous.co.uk/tesco.php">Tesco Debug</a></li>
</ul>
';
    }
}
