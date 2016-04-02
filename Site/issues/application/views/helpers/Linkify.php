<?php

class Zend_View_Helper_Linkify extends Zend_View_Helper_Abstract
{
    /**
     * Convert text URLs to Markdown-style URLs
     */
    public function Linkify($text)
    {
        //Convert URL's to Markdown-style links
        $text = preg_replace('"\b(https?://\S+)"', '[$1]($1)', $text);
        
        //Convert #123 to link to issue
        $text = preg_replace('"#(\d+)"', '[<span class="hash">#</span>$1](/issues/$1)', $text);
        
        return $text;
    }
}
