<?php

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
    
    <div class="ui-widget-content ui-corner-bottom" id="' . $this->id . '_content">
        ' . (method_exists($this, 'content') ? $this->content() : '') . '
    </div>
</div>';        
    }

    protected function error($message, $severity = 0) {
        $levels = array(
            'normal'
        );
        $severity = $levels[$severity];
        return '<div id="error" class="error_' . $severity . '">' . $message . '</div>';
    }

    protected function cache($url) {
        
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
