<?php

class Zend_View_Helper_FormatSeconds extends Zend_View_Helper_Abstract
{
    public function FormatSeconds($seconds)
    {
        //Convert number of seconds to either seconds, minutes, hours, days, weeks
        if ($seconds < 60) //1 minute
        {
            //Display as seconds
            $result = $seconds;
            
            $text_singular = '%d second';
            $text_plural = '%d seconds';
        }
        elseif ($seconds < 3600) //1 hour
        {
            //Display as minutes
            $minutes = intval($seconds/60);
            $result = $minutes;
            
            $text_singular = '%d minute';
            $text_plural = '%d minutes';
        }
        elseif ($seconds < 86400) //1 day
        {
            //Display as hours
            $hours = intval($seconds/3600);
            $result = $hours;
            
            $text_singular = '%d hour';
            $text_plural = '%d hours';
        }
        else
        {
            //Display as days
            $days = intval($seconds/86400);
            
            //See if the number of days can be divided by 7 to be weeks
            $weeks = strval(($days / 7));
            
            if (ctype_digit($weeks))
            {
                $result = $weeks;
                
                $text_singular = '%d week';
                $text_plural = '%d weeks';
            }
            else
            {
                  $result = $days;
              
                  $text_singular = '%d day';
                $text_plural = '%d days';
            }
        }
        
        //Format the result
        if ($result > 1 || $result == 0)
        {
            //Display as plural
            $result = sprintf($text_plural, $result);
        }
        else
        {
            //Display as singular
            $result = sprintf($text_singular, $result);
        }
        
        return $result;    
    }
}

?>