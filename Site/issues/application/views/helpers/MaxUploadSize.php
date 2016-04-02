<?php

class Zend_View_Helper_MaxUploadSize extends Zend_View_Helper_Abstract
{
    public function MaxUploadSize()
    {
        $max = 0;
        
        //Get the max upload sizes (use the lowest value)
        $post   = $this->_stringToBytes(ini_get('post_max_size'));
        $upload = $this->_stringToBytes(ini_get('upload_max_filesize'));
        $bytes  = ($post < $upload) ? $post : $upload;
        
        return $this->view->FormatBytes($bytes);
    }
    
    private function _stringToBytes($string)
    {
        $string = trim($string);
        $number = substr($string, 0, -1);
        $type   = strtolower(substr($string, -1));
        
        switch ($type)
        {
            case 'g':
                $number *= 1024;
            case 'm':
                $number *= 1024;
            case 'k':
                $number *= 1024;
        }
        
        return $number;
    }
}
