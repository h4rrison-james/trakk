<?php

class Bugify_Host {
    
    public static function getHostname($include_scheme=false) {
        $config = Zend_Registry::get('config');
        
        //Check if the app is running in the browser or cli
        if (isset($_SERVER['HTTP_HOST'])) {
            //Running in the browser
            $hostname = $_SERVER['HTTP_HOST'];
            
            //Check if the hostname has changed
            if (!isset($config->hostname) || $hostname != $config->hostname) {
                //Work out the path to the custom config file
                $config_path = $config->base_path.'/library/config.php';
                
                if (file_exists($config_path) && is_writable($config_path)) {
                    //Load the custom config file
                    $custom_config = new Zend_Config(require $config_path, true);
                    
                    //Set the new hostname
                    $custom_config->hostname = $hostname;
                    
                    //Save the config file
                    $w = new Zend_Config_Writer_Array();
                    $w->setConfig($custom_config);
                    
                    //Save the config file
                    file_put_contents($config_path, $w->render());
                } else {
                    //Cannot write to the config file - no big deal
                }
            }
        } else {
            //Running from the cli, check if we have a saved hostname
            if (isset($config->hostname) && strlen($config->hostname) > 0) {
                //Use the saved hostname
                $hostname = $config->hostname;
            } else {
                //We don't have a saved hostname
                $hostname = 'localhost';
            }
        }
        
        if ($include_scheme === true)
        {
            $scheme   = (isset($_SERVER['HTTPS']) && strlen($_SERVER['HTTPS']) > 0) ? 'https://' : 'http://';
            $hostname = $scheme.$hostname;
        }
        
        return strtolower($hostname);
    }
}
