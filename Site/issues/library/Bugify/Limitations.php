<?php

class Bugify_Limitations {
    const THEORETICAL_MAX = 9999999999;
    
    public static function getMaxUsers() {
        $config = Zend_Registry::get('config');
        $max    = $config->limitations->max_users;
        $max    = ((int)$max > 0) ? $max : self::THEORETICAL_MAX;
        
        return $max;
    }
    
    public static function getMaxProjects() {
        $config = Zend_Registry::get('config');
        $max    = $config->limitations->max_projects;
        $max    = ((int)$max > 0) ? $max : self::THEORETICAL_MAX;
        
        return $max;
    }
    
    /**
     * Maximum disk size of all attachments.
     */
    public static function getMaxSize() {
        $config = Zend_Registry::get('config');
        $max    = $config->limitations->max_size;
        $max    = ((int)$max > 0) ? $max : self::THEORETICAL_MAX;
        
        return $max;
    }
}
