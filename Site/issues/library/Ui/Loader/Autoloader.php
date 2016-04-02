<?php

class Ui_Loader_Autoloader {
    /**
     * When in debug mode, we log the Zend_* classes.
     * This is used to know which Zend Framework libraries are
     * being used.  The ones that arent can be dropped.
     */
    private static $_debug = false;
    
    public static function setDebug($val) {
        self::$_debug = (bool)$val;
    }
    
    public static function autoload($class) {
        if (self::$_debug === true) {
            if (substr($class, 0, strlen('Zend_')) == 'Zend_') {
                $filename = sys_get_temp_dir().'/bugify-zf.log';
                
                file_put_contents($filename, $class."\n", FILE_APPEND);
            }
        }
        
        $replace_vars = array('_', '\\');
        
        $path = str_replace($replace_vars, DIRECTORY_SEPARATOR, $class).'.php';
        
        include $path;
    }
    
    /**
     * Register the autoloader with spl_autoload registry
     */
    public function register() {
        spl_autoload_register(array($this, 'autoload'));
    }
}
