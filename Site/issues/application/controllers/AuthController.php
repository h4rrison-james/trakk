<?php

class AuthController extends Ui_Controller_Action {
    public function init() {
        $this->_helper->layout->setLayout('login');
    }
    
    public function loginAction() {
        try {
            //Make sure the db tables exist
            $u = new Bugify_Db_Upgrade();
            $u->upgradeDbSchema();
            
            //Make sure we have at least one user in the database
            $u = new Bugify_Users();
            $userCount = $u->fetchCount();
            
            if ($userCount == 0) {
                $this->_redirect('/add-user');
            }
            
            //Check if this is a new installation
            $params = $this->_getAllParams();
            $is_new = (isset($params['new'])) ? true : false;
            
            //Check if demo is enabled
            $config  = Zend_Registry::get('config');
            $is_demo = ($config->demo->enabled === true) ? true : false;
            $demo    = array(
                'username'     => $config->demo->username,
                'password'     => $config->demo->password,
                'reset_period' => $config->demo->reset_period,
            );
            
            //Set defaults
            $username = '';
            
            if ($this->getRequest()->isPost()) {
                $username = $this->_getParam('username');
                $password = $this->_getParam('password');
                
                if (strlen($username) > 0 && strlen($password) > 0) {
                    $a    = new Bugify_Auth();
                    $user = $a->auth($username, $password);
                    
                    if ($user !== false) {
                        //Use a new session id
                        Zend_Session::regenerateId();
                        
                        //Store the user details
                        $auth = new Zend_Session_Namespace('Auth');
                        $auth->user = $user;
                        
                        /**
                         * Check if the user was trying to visit an issue
                         * before they were forced to log in.
                         */
                        $issue_id = (int)$this->_getParam('issue');
                        
                        if ($issue_id > 0) {
                            $this->_redirect(sprintf('/issues/%s', $issue_id));
                        }
                        
                        $this->_redirect('/');
                    }
                } else {
                    throw new Ui_Exception('Please type both your username and password.');
                }
            }
            
            $this->view->username = $username;
            $this->view->is_new   = $is_new;
            $this->view->is_demo  = $is_demo;
            $this->view->demo     = $demo;
        } catch (Exception $e) {
            $this->view->login_error = $e->getMessage();
        }
    }
    
    public function logoutAction() {
        try {
            Zend_Session::destroy(true);
            $this->_redirect('/login');
        } catch (Exception $e) {
            Ui_Messages::Add('error', $e->getMessage());
        }
    }
    
    public function addUserAction() {
        try {
            //Only ever allow adding a user here if there are NONE in the db
            $u = new Bugify_Users();
            $userCount = $u->fetchCount();
            
            if ($userCount == 0) {
                //Prepare the defaults
                $user = array(
                   'firstname' => '',
                   'lastname'  => '',
                   'email'     => '',
                   'username'  => '',
                   'password'  => '',
                   'timezone'  => 'Europe/London',
                );
                
                if ($this->getRequest()->isPost()) {
                    $user = $this->_getParam('user');
                    
                    $u = new Bugify_User();
                    $u->setFirstname($user['firstname'])
                      ->setLastname($user['lastname'])
                      ->setEmail($user['email'])
                      ->setUsername($user['username'])
                      ->setPlainTextPassword($user['password'])
                      ->setTimezone($user['timezone'])
                      ->setState(Bugify_User::STATE_ACTIVE);
                    
                    //Save the user
                    $users = new Bugify_Users();
                    $users->save($u);
                    
                    $this->_redirect('/login?new');
                }
                
                $this->view->add_user = $user;
            } else {
                $this->_redirect('/');
            }
        } catch (Exception $e) {
            Ui_Messages::Add('error', $e->getMessage());
        }
    }
}
