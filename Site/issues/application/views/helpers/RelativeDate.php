<?php

class Zend_View_Helper_RelativeDate extends Zend_View_Helper_Abstract {
    public function RelativeDate($dateString, $nowString='') {
        //Make sure this is a valid date
        if (!Zend_Date::isDate($dateString, Zend_Date::ISO_8601)) {
            //Make up a date
            $dateString = '1970-01-01T00:00:00+00:00';
        }
        
        $date = new Zend_Date();
        $date->setTimezone(Bugify_Date::getUserTimezone());
        $date->set($dateString, Zend_Date::ISO_8601);
        
        if ($date->getTimestamp() > 0)
        {
            $now = (strlen($nowString) > 0) ? new Zend_Date($nowString, Zend_Date::ISO_8601) : new Zend_Date();
            $now->setTimezone(Bugify_Date::getUserTimezone());
            
            $seconds_ago = ($now->getTimestamp() - $date->getTimestamp());
            
            //Work out the date in a relative format (eg, less than a minute ago.  Yesterday.  X days ago. etc)
            if ($seconds_ago <= 60)
            {
                //Less than a minute ago
                $output = 'less than a minute ago';
            }
            elseif ($seconds_ago <= 3600)
            {
                //Was less than an hour, work out how many minutes
                $minutes_ago = ceil($seconds_ago / 60);
                
                if ($minutes_ago > 1)
                {
                    $output = $minutes_ago.' minutes ago';
                }
                else
                {
                    $output = $minutes_ago.' minute ago';
                }
            }
            elseif ($seconds_ago <= 86400)
            {
                //Was in the last 24 hours, so work out how many hours ago
                $hours_ago = ceil($seconds_ago / 3600);
                
                if ($hours_ago > 1)
                {
                    $output = $hours_ago.' hours ago';
                }
                else
                {
                    $output = 'about an hour ago';
                }
            }
            elseif ($seconds_ago <= 172800)
            {
                //Was in the last 48 hours
                $output = 'yesterday';
            }
            elseif ($seconds_ago <= 604800)
            {
                //Was in the last 7 days, so work out how many days ago
                $days_ago = ceil($seconds_ago / 86400);
                $output = $days_ago.' days ago';
            }
            elseif ($seconds_ago <= 5184000)
            {
                //Was in the last 60 days, work out how many weeks ago
                $weeks_ago = ceil($seconds_ago / 604800);
                
                if ($weeks_ago > 1)
                {
                    $output = 'about '.$weeks_ago.' weeks ago';
                }
                else
                {
                    $output = 'about a week ago';
                }
            }
            elseif ($seconds_ago <= 31536000)
            {
                //Was in the last year, work out how many months
                $months_ago = ceil($seconds_ago / 2592000);
                
                if ($months_ago > 1)
                {
                    $output = 'about '.$months_ago.' months ago';
                }
                else
                {
                    $output = 'about '.$months_ago.' month ago';
                }
            }
            else
            {
                //Work out how many years ago
                $years_ago = ceil($seconds_ago / 31536000);
                
                if ($years_ago > 1)
                {
                    $output = 'about '.$years_ago.' years ago';
                }
                else
                {
                    $output = 'about a year ago';
                }
            }
        }
        else
        {
            $output = 'Never';
        }
        
        return $output;
    }
}