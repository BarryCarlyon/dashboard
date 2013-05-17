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
#reprinttesco_result { text-align: center; }
</style>
<script type="text/javascript">
jQuery(document).ready(function() {
    jQuery(\'#reprinttesco_prompt\').hide();

    jQuery(\'#reprinttesco_prompt\').dialog({
        title: \'Print a Tesco Click and Collect\',
        width: 450,
        height: 200,
        modal: true,
        autoOpen: false,
    });

    jQuery(\'#reprinttesco\').click(function(e) {
        e.preventDefault();
        jQuery(\'#reprinttesco_result\').html(\'\');
        jQuery(\'#reprinttesco_prompt\').dialog(\'open\');
        jQuery(\'#reprinttesco_workorder\').focus();
    })
    jQuery(\'#reprinttesco_prompt\').submit(function(e) {
        e.preventDefault();
        jQuery(\'#reprinttesco_result\').html(\'Loading <img src="/icons/loading.gif" />\');
        jQuery.ajax({
            data: {
                order_id: jQuery(\'#reprinttesco_workorder\').val(),
                stop: 1
            },
            type: \'POST\',
            url: \'/doatesco.php\',
            success: function(data) {
                jQuery(\'#reprinttesco_result\').html(data);
            }
        });
        jQuery(\'#reprinttesco_workorder\').val(\'\');
    });
})
</script>
<ul class="links">
    <li><a href="orders.php">Work Orders</a></li>
    <li><a href="#tesco" id="reprinttesco">RePrint a Tesco Order</a></li>
    <li><a href="http://tools.fredaldous.co.uk/tesco.php">Tesco Debug</a></li>
</ul>
<form id="reprinttesco_prompt">
Scan Work Order > <input type="text" name="reprinttesco_workorder" id="reprinttesco_workorder" />
<div id="reprinttesco_result"></div>
</form>
';
    }
}
