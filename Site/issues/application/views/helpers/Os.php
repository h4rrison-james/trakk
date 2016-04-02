<?php

class Zend_View_Helper_Os extends Zend_View_Helper_Abstract
{
    /**
     * Detect the operating system that the user is using.
     * Doesnt need to be 100% accurate.
     * 
     * @param  $is_mobile  Specify true to find out if the users' OS is a mobile device
     */
    public function Os($is_mobile=false)
    {
        $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $os        = 'Unknown';
        $mobile    = false;
        
        $systems = array(
           'Mac' => array(
              'strings' => array(
                 'macintosh',
              ),
              'mobile' => false,
           ),
           'Windows' => array(
              'strings' => array(
                 'windows',
              ),
              'mobile' => false,
           ),
           'iOS' => array(
              'strings' => array(
                 'iphone',
                 'ipad',
              ),
              'mobile' => true,
           ),
           'Android' => array(
              'strings' => array(
                 'android',
              ),
              'mobile' => true,
           ),
           'Linux' => array(
              'strings' => array(
                 'linux',
              ),
              'mobile' => false,
           ),
        );
        
        foreach ($systems as $os => $val)
        {
            foreach ($val['strings'] as $string)
            {
                if (strpos($useragent, $string) !== false)
                {
                    //Found the os
                    $mobile = $val['mobile'];
                    break 2;
                }
            }
        }
        
        return ($is_mobile === false) ? $os : $mobile;
    }
}
