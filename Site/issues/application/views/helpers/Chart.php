<?php

class Zend_View_Helper_Chart extends Zend_View_Helper_Abstract {
    public function Chart($name, $width=100, $height=100, $title='') {
        $url  = sprintf('/assets/chart/%s/%s/%s', urlencode($name), urlencode($width), urlencode($height));
        $html = sprintf('<img src="%s" alt="%s" title="%s" width="%s" height="%s" class="chart" />', $url, $title, $title, $width, $height);
        
        return $html;
    }
}
