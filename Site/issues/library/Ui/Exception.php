<?php

class Ui_Exception extends Exception
{
    public function __construct($message, $code=0, Exception $previous=null)
    {
        //Versions prior to 5.3 do not support the previous variable
        if (version_compare(PHP_VERSION, '5.3.0', '<'))
        {
            parent::__construct($message, (int)$code);
            
            $this->_previous = $previous;
        }
        else
        {
            parent::__construct($message, (int)$code, $previous);
        }
        
        if (class_exists('Zend_Registry') && Zend_Registry::isRegistered('logger'))
        {
            //Log the error
            $logger = Zend_Registry::get('logger');
            $logger->err($this);
        }
    }
}
