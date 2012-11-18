<?php

function columnRender($name, $width, $content = '') {
    return '<div class="col" id="' . $name . '" style="width: ' . $width . 'px;">'
        . '<hr /><div class="col_control"><ul>'
        . '<li class="change_col">Change</li>'
        . '<li class="delete_col">Delete</li>'
        . '</ul>Control</div><hr />' . $content . '</div>';
}

class module {
    public function generate($closed = false)
    {
        return '<div class="ui-widget ui-widget-content ui-corner-all module" id="' . $this->id . '_' . ($closed ? 'closed' : 'widget') . '">
    <div class="ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
        ' . $this->title . '
        <span class="toggle ui-icon ui-icon-' . ($closed ? 'plus' : 'minus') . 'thick"></span>
    </div>
    
    <div class="ui-widget-content ui-corner-bottom widget-content" id="' . $this->id . '_content" style="display: ' . ($closed ? 'none' : 'block') . ';">
        ' . (method_exists($this, 'header') ? $this->header() : '') . '
        ' . (method_exists($this, 'content') ? $this->content() : '') . '
    </div>
</div>';
    }
    public function titleOnly()
    {
        return '<div class="ui-widget ui-widget-content ui-corner-all module" id="' . $this->id . '_widget">
    <div class="ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
        ' . $this->title . '
        <span class="toggle ui-icon ui-icon-plusthick hidden"></span>
    </div>
    <div class="module_content"></div>
</div>';
    }
    public function bodyOnly() {
        return '
    <div class="ui-widget-content ui-corner-bottom widget-content" id="' . $this->id . '_content">
        ' . (method_exists($this, 'header') ? $this->header() : '') . '
        ' . (method_exists($this, 'content') ? $this->content() : '') . '
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
