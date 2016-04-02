<?php

class Zend_View_Helper_Gravatar extends Zend_View_Helper_Abstract
{
    public function Gravatar($name, $email, $size=32)
    {
        $email = (strlen($email) > 0) ? $email : '-';
        $url   = '/assets/gravatar/'.urlencode($size).'/'.urlencode($email);
        
        $img = sprintf('<img src="%s" title="%s" width="%s" height="%s" class="gravatar" />', $url, $name, $size, $size);
        
        return $img;
    }
}
