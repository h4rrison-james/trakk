<?php

class AccountController extends Ui_Controller_Action {
    public function init() {
        $config = Zend_Registry::get('config');
        
        if ($config->hosted !== true) {
            throw new Bugify_Exception('The account management interface is for hosted sites only.', 404);
        }
    }
    
    public function indexAction() {
        try {
            //Load the current limitations
            $limitations = array(
                'projects' => Bugify_Limitations::getMaxProjects(),
                'users'    => Bugify_Limitations::getMaxUsers(),
                'size'     => Bugify_Limitations::getMaxSize(),
            );
            
            if ($limitations['projects'] == Bugify_Limitations::THEORETICAL_MAX) {
                $limitations['projects'] = 'Unlimited';
            }
            
            if ($limitations['users'] == Bugify_Limitations::THEORETICAL_MAX) {
                $limitations['users'] = 'Unlimited';
            }
            
            if ($limitations['size'] == Bugify_Limitations::THEORETICAL_MAX) {
                $limitations['size'] = 'Unlimited';
            }
            
        } catch (Exception $e) {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->limitations = $limitations;
    }
}