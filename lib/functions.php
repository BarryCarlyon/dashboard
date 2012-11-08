<?php

function columnRender($name, $width) {
    return '<div class="col" id="' . $name . '" style="width: ' . $width . 'px;"></div>';
}

class module {
    function generate()
    {
        if (method_exists($this, 'header')) {
            echo $this->header();
        }
        return '<div class="ui-widget ui-widget-content ui-corner-all module" id="' . $this->id . '_widget">
    <div class="ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
        ' . $this->title . '
    </div>
    
    <div class="ui-widget-content ui-corner-bottom widget-content" id="' . $this->id . '_content">
        ' . (method_exists($this, 'content') ? $this->content() : '') . '
    </div>
</div>';        
    }

    protected function error($message, $severity = 0) {
        $levels = array(
            'normal'
        );
        $severity = $levels[$severity];
        return '<div id="error error_' . $severity . '">' . $message . '</div>';
    }

    // 4 hours
    protected function cache($url, $method = 'get', $max_age = 21600) {
        $encoded = md5($url);
        if (file_exists(DASHBOARD_CACHE_PATH . $encoded)) {
            $time = filemtime(DASHBOARD_CACHE_PATH . $encoded);
            if ( (time() - $max_age) < $time) {
                return file_get_contents(DASHBOARD_CACHE_PATH . $encoded);
            }
        }
        $data = $this->fetch($url, $method);
        if ($data) {
            $fp = fopen(DASHBOARD_CACHE_PATH . $encoded, 'w');
            fwrite($fp, $data);
            fclose($fp);
        }
        return $data;
    }

    protected function fetch($url, $method = 'get')
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($method == 3) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        } elseif ($method == 2) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } elseif ($method == 1) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($response === false) {
            return false;
        }
        if ($code == 200) {
            return $response;
        }
        return false;
    }
}
