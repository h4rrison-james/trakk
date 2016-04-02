<?php

class ErrorController extends Ui_Controller_Action {
    public function errorAction() {
        $this->_helper->layout->setLayout('error');
        
        $errors    = $this->_getParam('error_handler');
        $exception = $errors->exception;
        
        //Default to 500 error
        $error_type = 500;
        
        if ($exception->getCode() == 404) {
            $error_type = 404;
        } else {
            switch ($errors->type) {
                case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
                case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                    $error_type = 404;
                    break;
            }
        }

        switch ($error_type) {
            case 404:
                // 404 error -- controller or action not found
                $this->getResponse()
                     ->setRawHeader('HTTP/1.1 404 Not Found');
                
                $this->view->raw_error  = $exception->getMessage();
                $this->view->stacktrace = $exception->getTraceAsString();
                $this->view->page_title = '404 - Page not found';
                
                break;
            default:
                
                //Application error
                $this->getResponse()
                     ->setRawHeader('HTTP/1.1 500 Internal Server Error');
                
                $this->view->raw_error  = $exception->getMessage();
                $this->view->stacktrace = $exception->getTraceAsString();
                $this->view->page_title = '500 - Application error';
                
                break;
        }
        
        //Find out if we should show verbose errors or not
        $config = Zend_Registry::get('config');
        
        $this->view->show_verbose = ($config->errors->verbose) ? true : false;
        $this->view->error_type   = $error_type;
    }
}
