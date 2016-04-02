<?php

class Ui_Exception_Handler extends Exception {
    public static function errorHandlerCallback($code, $string, $file, $line, $context) {
        $e = new self($string, $code);
        $e->line = $line;
        $e->file = $file;
        
        if (class_exists('Zend_Registry') && Zend_Registry::isRegistered('logger')) {
            //Log the error
            $logger = Zend_Registry::get('logger');
            $logger->err($e);
        }
        
        throw $e;
    }
}
