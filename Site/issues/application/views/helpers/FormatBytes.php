<?php

class Zend_View_Helper_FormatBytes extends Zend_View_Helper_Abstract
{
    public function FormatBytes($a)
    {
        $unit = array(
           'Bytes',
           'KB',
           'MB',
           'GB',
           'TB',
           'PB',
        );
        
        $i = 0;
        
        while ($a >= 1024)
        {
            $a = ($a / 1024);
            
            $i++;
        }
        
        //Decide if the result should have decimal places or not
        switch ($unit[$i])
        {
            case 'MB':
                //Only use decimal places if neccessary
                if (strpos($a, '.') !== false)
                {
                    //Has decimal place
                    $decimals = 2;
                }
                else
                {
                    //No decimal place
                    $decimals = 0;
                }
                
                break;
            default:
                //Use decimal places by default
                $decimals = 2;
        }
        
        return number_format($a, $decimals, '.', ',').' '.$unit[$i];
    }
}
