<?php

class Zend_View_Helper_ShortenString extends Zend_View_Helper_Abstract
{
    public function ShortenString($string, $length=255, $last_percent=25, $separator='...')
    {
        //Remove all new-line characters from the string
        $string = str_replace("\n", ' ', $string);
        
        //Convert a long string into a shorter one using ... (dots) where necessary
        if (strlen($string) > ($length - strlen($separator)))
        {
            //Get the last $last_percent% of the characters
            $chars      = ($last_percent / 100) * $length;
            $last_chars = substr($string, -$chars);
            
            //Now get the first characters
            $chars       = (($length - strlen($separator)) - strlen($last_chars));
            $first_chars = substr($string, 0, $chars);
            
            //Put them together
            $string = $first_chars.$separator.$last_chars;
        }
        
        return $string;
    }
}
