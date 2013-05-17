<?php

$widgets[] = '
<li id="clock" data-col="5" data-row="1" data-sizex="1" data-sizey="1">
    <div style="width: 200px; margin-left: auto; margin-right: auto; margin-top: 25px;">
        <div id="clock_clock"></div>
        <div class="date tcenter">' . date('d/m/Y', time()) . '</div>
            <script type="text/javascript">
                jQuery(document).ready(function() {
                    swfobject.embedSWF("/widgets/clock/tiny.swf", "clock_clock", "200", "200", "9.0.0", "", {}, {wmode: "transparent"});
                });
            </script>
    </div>
</li>
';
