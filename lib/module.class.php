<?php

class Module {
    public function generate($first = false)
    {
        return '<div class="module" id="' . $this->id . '_widget">
    <div class="module_title" style="display: none;">' . $this->title . '</div>
    ' . $this->bodyOnly($first) . '
    ' . $this->controls() . '
</div>';
    }
    public function titleOnly()
    {
        return '<div class="module" id="' . $this->id . '_widget">
    <div class="module_title">' . $this->title . '</div>
    <div class="module_content"></div>
    ' . $this->controls() . '
</div>';
    }
    public function bodyOnly($first = false)
    {
        return '
    <div id="' . $this->id . '_content" class="module_content">
        ' . (method_exists($this, 'header') ? $this->header($first) : '') . '
        ' . (method_exists($this, 'content') ? $this->content($first) : '') . '
    </div>';
    }

    protected function controls() {
        $html = '<div class="module_control">';
        $html .= '<h5>Options</h5>';
        if (method_exists($this, 'options')) {
            $html .= '<br /><a href="#edit" class="edit-icon editwidget">Edit Widget</a>';
        }
        $html .= '<br /><a href="#delete" class="delete-icon removewidget">Remove Widget</a>';
        $html .= '</div>';
        return $html;
    }
    public function editControls() {
        $html = '<form action="" method="post" id="editControls">';

        $options = $this->options();
        foreach ($options as $name => $data) {
            $append = $class = '';
            switch ($data['type']) {
                case 'multiple':
                    $html .= $data['name'] . '<br />';
                    foreach ($data['values'] as $index => $value) {
                        if ($data['keys']) {
                            $html .= '<input type="text" name="' . $data['key_name'] . '[]" class="duplicate" value="' . $index . '" style="width: 40%;" />: ';
                        }
                        $html .= '<input type="text" name="' . $name . '[]" class="duplicate" value="' . $value . '" style="width: 40%;" /><br />';
                    }
                    break;
                case 'text':
                    $html .= '<input type="text" name="' . $name . '" />';
                    break;
            }
            $html .= '<br />';
        }

        $html .= '</form>';
        // submit

        return $html;
    }

    protected function error($message, $severity = 0) {
        $levels = array(
            'normal'
        );
        $severity = $levels[$severity];
        return '<div class="error error_' . $severity . '">' . $message . '</div>';
    }

    protected function loadcache($url)
    {
        $encoded = md5($url);
        if (file_exists(DASHBOARD_CACHE_PATH . $encoded)) {
            return file_get_contents(DASHBOARD_CACHE_PATH . $encoded);
        }
        return '';
    }

    // 4 hours
    protected function cache($url, $method = 'get', $max_age = 21600)
    {
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
    protected function cacheData($data, $file) {
        $file = md5($file);
        $fp = fopen(DASHBOARD_CACHE_PATH . $file, 'w');
        fwrite($fp, $data);
        fclose($fp);
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

        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Mobile/7B405');

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

class JsonModule extends Module {
    public function content()
    {
        $response = false;
        $data = $this->cache($this->url, 3600);
        if ($data) {
            $data = json_decode($data);
            if (json_last_error() == JSON_ERROR_NONE) {
                $response = $data;
            }
        }
        return $this->parse($response);
    }
}
class XmlModule extends Module {
    public function __construct() {
        require_once(DASHBOARD_LIB_PATH . 'simplepie.inc');
    }
    public function content() {
        $response = false;

        $feed = new SimplePie();
        $feed->set_feed_url($this->urls);
        $feed->set_cache_location(DASHBOARD_CACHE_PATH);
        $feed->init();
        $feed->handle_content_type();

        if (count($feed->get_items())) {
            $response = $feed;
        }

        return $this->parse($response);
    }
}

