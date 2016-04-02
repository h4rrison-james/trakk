<?php

class Zend_View_Helper_ExtensionIcon extends Zend_View_Helper_Abstract
{
    public function ExtensionIcon($path)
    {
        $folder = '/images/icons/16/ext/';
        
        //Work out the extension
        $ext = (strpos($path, '.') !== false) ? substr($path, strrpos($path, '.')+1) : '';
        
        switch ($ext)
        {
            case 'css':
                $icon = 'css.png';
                break;
            case 'php':
                $icon = 'php.png';
                break;
            case 'jpg':
            case 'jpeg':
            case 'gif':
            case 'png':
                $icon = 'image.png';
                break;
            case 'html':
            case 'phtml':
            case 'py':
                $icon = 'generic.png';
                break;
            case 'txt':
                $icon = 'plain.png';
                break;
            case 'js':
                $icon = 'script.png';
                break;
            case 'pdf':
                $icon = 'pdf.png';
                break;
            case 'zip':
                $icon = 'zip.png';
                break;
            case 'mp3':
            case 'wav':
                $icon = 'audio.png';
                break;
            default:
                $icon = 'generic.png';
        }
        
        return $folder.$icon;
    }
}
