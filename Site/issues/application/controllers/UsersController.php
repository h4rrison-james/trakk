<?php

class UsersController extends Ui_Controller_Action
{
    public function init()
    {}
    
    public function indexAction()
    {
        try
        {
            $users = array();
            
            //Fetch all users
            $u = new Bugify_Users();
            $result = $u->fetchAll();
            
            //Process the projects (attach issue counts etc)
            $h = new Bugify_Helpers_Issues();
            $users = $h->addIssueCountsForAssignees($result);
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->users = $users;
    }
    
    public function issuesAction()
    {
        try
        {
            $user   = array();
            $issues = array();
            $page   = 1;
            $limit  = 0;
            $total  = 0;
            
            $username = $this->_getParam('username');
            
            //Load the user details
            $u = new Bugify_Users();
            $result = $u->fetch($username);
            $user   = $result->toArray();
            
            //Load all open issues for this user
            $i = new Bugify_Issues();
            $i->setPaginationPage((int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1);
            
            //Set the filter options
            $filter = $i->filter();
            $filter->setAssigneeIds(array($result->getUserId()));
            $result = $i->fetchAll($filter);
            
            //Process the issues (ie, attach extra info)
            $h = new Bugify_Helpers_Issues();
            $issues = $h->processIssues($result);
            
            //Get the pagination info
            $page  = $i->getPaginationPage();
            $limit = $i->getPaginationLimit();
            $total = $i->getTotal();
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->user   = $user;
        $this->view->issues = $issues;
        $this->view->page   = $page;
        $this->view->limit  = $limit;
        $this->view->total  = $total;
    }
    
    public function newAction()
    {
        try
        {
            //Prepare the defaults
            $user = array(
               'firstname' => '',
               'lastname'  => '',
               'email'     => '',
               'username'  => '',
               'password'  => '',
            );
            
            if ($this->getRequest()->isPost())
            {
                $user = $this->_getParam('user');
                
                $u = new Bugify_User();
                $u->setFirstname($user['firstname'])
                  ->setLastname($user['lastname'])
                  ->setEmail($user['email'])
                  ->setUsername($user['username'])
                  ->setPlainTextPassword($user['password'])
                  ->setTimezone($this->user->getTimezone()) //Use same timezone as current user
                  ->setState(Bugify_User::STATE_ACTIVE);
                
                //Save the user
                $users = new Bugify_Users();
                $users->save($u);
                
                Ui_Messages::Add('ok', 'User has been saved.');
                
                $this->_redirect('/users');
            }
            
            $this->view->title    = 'Add User';
            $this->view->add_user = $user;
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
    }
    
    public function editAction()
    {
        try
        {
            $username = $this->_getParam('username');
            $api_key  = '';
            $user     = array();
            
            //Load the user details
            $users = new Bugify_Users();
            $u     = $users->fetch($username);
            
            //Check if this is the logged-in user
            if ($u->getUsername() == $this->user->getUsername())
            {
                //Allow the logged-in user to see their own Api key
                $api_key = $u->getApiKey();
            }
            
            //Get the user details
            $user = $u->toArray();
            
            if ($this->getRequest()->isPost())
            {
                $formName = $this->_getParam('formName');
                
                //Figure out which form was posted
                if ($formName == 'user') {
                    $editUser = $this->_getParam('user');
                    
                    //Update the user
                    $u->setFirstname($editUser['firstname'])
                      ->setLastname($editUser['lastname'])
                      ->setEmail($editUser['email'])
                      ->setTimezone($editUser['timezone']);
                    
                    if (strlen($editUser['password']) > 0)
                    {
                        //Dont let the password change in demo mode
                        $config = Zend_Registry::get('config');
                        
                        if ($config->demo->enabled === false) {
                            $u->setPlainTextPassword($editUser['password']);
                        } else {
                            throw new Bugify_Exception('You cannot change the password while demo mode is enabled.');
                        }
                    }
                    
                    //Save the user
                    $users->save($u);
                    
                    //Check if this was the user that is logged in
                    if ($u->getUserId() == $this->user->getUserId())
                    {
                        /**
                         * Update the users' details in the auth session, otherwise
                         * it doesnt get updated until the user logs in again.
                         */
                        $auth = new Zend_Session_Namespace('Auth');
                        $auth->user->setFirstname($u->getFirstname())
                                   ->setLastname($u->getLastname())
                                   ->setEmail($u->getEmail())
                                   ->setTimezone($u->getTimezone());
                    }
                    
                    Ui_Messages::Add('ok', 'User has been saved.');
                    
                    $this->_redirect('/users');
                } elseif ($formName == 'notification') {
                    //Update the notification settings
                    $notification = $this->_getParam('notification');
                    
                    if (!is_array($notification)) {
                        //None of the options were checked
                        $notification = array();
                    }
                    
                    /**
                     * First, we need to update the $notification array to contain the
                     * disabled notification types.
                     */
                    foreach ($u->getValidNotificationTypes() as $key => $val) {
                        if (!array_key_exists($val, $notification)) {
                            $notification[$val] = false;
                        }
                    }
                    
                    foreach ($notification as $key => $val) {
                        $u->setRequiresNotification($key, (bool)$val);
                    }
                    
                    //Save the user
                    $users->save($u);
                    
                    Ui_Messages::Add('ok', 'Notification settings have been saved.');
                    
                    $this->_redirect(sprintf('/users/%s/edit', $u->getUsername()));
                } elseif ($formName == 'apikey') {
                    //Generate a new API key for this user
                    $u->setApiKey($u->generateApiKey());
                    $users->save($u);
                    
                    Ui_Messages::Add('ok', 'A new API key has been generated.');
                    
                    $this->_redirect(sprintf('/users/%s/edit', $u->getUsername()));
                }
            }
        }
        catch (Exception $e)
        {
            if ($e->getCode() == 404)
            {
                throw $e;
            }
            
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->edit_user = $user;
        $this->view->api_key   = $api_key;
    }
    
    public function deleteAction()
    {
        try
        {
            $username = $this->_getParam('username');
            
            //Load the user details
            $users = new Bugify_Users();
            $u     = $users->fetch($username);
            
            //Get the user details
            $user = $u->toArray();
            
            if ($this->getRequest()->isPost())
            {
                //Change the user state
                $u->setState(Bugify_User::STATE_ARCHIVED);
                
                //Save the user
                $users->save($u);
                
                Ui_Messages::Add('ok', 'User has been deleted.');
                
                $this->_redirect('/users');
            }
        }
        catch (Exception $e)
        {
            if ($e->getCode() == 404)
            {
                throw $e;
            }
            
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->_redirect(sprintf('/users/%s/edit', $username));
    }
}
