<?php

class Zend_View_Helper_Markdown extends Zend_View_Helper_Abstract
{
    public function Markdown($text)
    {
        if (Zend_Registry::isRegistered('Markdown'))
        {
            $m = Zend_Registry::get('Markdown');
        }
        else
        {
            //This must be the first call to this helper, load Markdown
            $m = new Ui_Markdown_Parser();
            
            //Store the object for the next time we need it
            Zend_Registry::set('Markdown', $m);
        }
        
        return $m->transform($text);
    }
}
