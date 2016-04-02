<?php

class Ui_Controller_Action extends Zend_Controller_Action {
    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array()) {
        parent::__construct($request, $response, $invokeArgs = array());
        
        // Initialize view object immediately
        $this->initView();
        
        //Let the view know which controller we are using
        $this->view->controller = $this->getRequest()->getControllerName();
        $this->view->action     = $this->getRequest()->getActionName();
        
        //Load config
        $config = Zend_Registry::get('config');
        
        //Make sure the user is logged in
        $logged_in = false;
        
        if (Zend_Session::namespaceIsset('Auth')) {
            $auth = new Zend_Session_Namespace('Auth');
            
            if (isset($auth->user) && strlen($auth->user->getUsername()) > 0) {
                $logged_in        = true;
                $this->user       = $auth->user;
                $this->view->user = array(
                   'id'       => $auth->user->getUserId(),
                   'name'     => $auth->user->getName(),
                   'email'    => $auth->user->getEmail(),
                   'username' => $auth->user->getUsername(),
                   'timezone' => $auth->user->getTimezone(),
                );
                
                //Set the timezone for date output
                Bugify_Date::setUserTimezone($auth->user->getTimezone());
                
                if ($config->install->remove_reminder) {
                    //Check that the /install directory has been removed
                    if (is_dir($config->public_path.'/install')) {
                        $this->view->remove_install = true;
                    }
                }
            }
        }
        
        /**
         * List of controllers that do not require
         * authentication.
         */
        $no_auth_controllers = array(
           'auth',
           'error',
           'api', //Api has its own auth
        );
        
        if ($logged_in == false && !in_array($this->view->controller, $no_auth_controllers)) {
            //Check if the user was trying to access an issue
            if ($this->view->controller == 'issues' && $this->view->action == 'overview') {
                $issue_id = (int)$this->_getParam('issue_id');
                
                if ($issue_id > 0) {
                    $this->_redirect(sprintf('/login/?issue=%s', $issue_id));
                }
            }
            
            $this->_redirect('/login');
        }
        
        //Load the cache
        $this->cache = new Ui_Cache();
        $this->cache->setLifetime($config->cache->lifetime)
                    ->setCacheDir($config->base_path.$config->cache->cache_dir)
                    ->setEnabled($config->cache->enabled);
        
        /**
         * Save the cache object to the registry for use
         * in view helpers etc.
         */
        Zend_Registry::set('cache', $this->cache);
        
        //Make sure the licence key is set
        if (!isset($config->licence) || strlen($config->licence) == 0) {
            Ui_Messages::Add('warning', 'The licence key has not been set.  Please set the licence key in the config.php file.');
        }
        
        //Check if this is running as a hosted version
        $this->view->isHosted = $config->hosted;
        
        //Check for extra css
        $this->view->extra_css = $config->css;
        
        //Load the search index
        $this->search = new Bugify_Search($config->base_path.$config->lucene->index_path);
        
        //Pass the version to the view
        $this->view->app_version = ($config->upgrades->channel == 'stable') ? Bugify_Version::VERSION : sprintf('%s-%s', Bugify_Version::VERSION, $config->upgrades->channel);
        
        if ($logged_in === true) {
            try {
                //Check if there are any upgrades available
                $u = new Bugify_Upgrades($config->upgrades->url, $config->upgrades->channel);
                
                if ($u->shouldCheckForUpgrade()) {
                    $u->checkForUpgradeAsync();
                }
                
                /**
                 * The update check has been initiated asyncronously, but we might have
                 * upgrade info anyway.
                 */
                if ($u->upgradeExists()) {
                    if ($config->upgrades->show_reminder) {
                        $this->view->upgrade = $u->getUpgradeInfo();
                    }
                }
            } catch (Exception $e) {
                Ui_Messages::Add('warning', $e->getMessage());
            }
        }
        
        //Work out the path to the custom config file
        $config_path = $config->base_path.'/library/config.php';
        
        if (file_exists($config_path)) {
            if (function_exists('posix_getpwuid')) {
                //Find out which user the site is running as
                $user     = posix_getpwuid(posix_getuid());
                $username = (isset($user['name'])) ? $user['name'] : 'unknown';
                
                if ($config->web_user != $username) {
                    //Load a new copy of the config file
                    $custom_config = new Zend_Config(require $config_path, true);
                    
                    //Make sure the config file is writable
                    if (is_writable($config_path)) {
                        $custom_config->web_user = $username;
                        
                        //Save the config file
                        $w = new Zend_Config_Writer_Array();
                        $w->setConfig($custom_config);
                        
                        //Save the config file
                        if (file_put_contents($config_path, $w->render()) === false) {
                            throw new Bugify_Exception('Could not save settings.  Please check the permissions on the config file.');
                        }
                    } else {
                        throw new Bugify_Exception('Settings cannot be saved because the config file is not writable.');
                    }
                }
            }
        }
    }
    
    public function render($action = null, $name = null, $noController = false) {
        //This method catches any instance where $this->render() is explicitely called from an action
        $this->escapeAllVars();
        
        parent::render($action, $name, $noController);
    }
    
    public function postDispatch() {
        //This method is run just before the view is rendered
        $this->escapeAllVars();
    }
    
    private function escapeAllVars() {
        //Get all variables assigned to the view
        $vars = $this->_helper->layout->getLayoutInstance()->getView()->getVars();
        
        //Clear all variables assigned to the view
        $this->_helper->layout->getLayoutInstance()->getView()->clearVars();        
        
        if (is_array($vars) && count($vars) > 0) {
            //Go through each variable and escape the data
            foreach ($vars as $key => $val) {
                if (is_string($val)) {
                    //Escape the string/number
                    $this->escapeVar($val, $key);
                } elseif (is_array($val)) {
                    //Recursively escape all data in this array
                    array_walk_recursive($val, array(&$this, 'escapeVar'));
                } elseif (is_object($val)) {
                    if ($val instanceof Zend_Session_Namespace) {
                        //Step through all variables in the session
                        foreach ($val as $index => $value) {
                            if (is_string($value)) {
                                $this->escapeVar($value, $key);
                            } elseif (is_array($value)) {
                                array_walk_recursive($value, array(&$this, 'escapeVar'));
                            } elseif (is_null($value) || is_bool($value) || is_numeric($value)) {
                                //Dont need to escape
                            } else {
                                throw new Ui_Exception('The specified variable \''.$index.'\' is of type \''.gettype($value).'\'.  Please use either string or array.');
                            }
                            
                            $this->escapeVar($index, $key);
                            
                            $val->$index = $value;
                        }
                    } else {
                        throw new Ui_Exception('The specified variable \''.$key.'\' is of type \''.gettype($val).'\'.  Please use either string or array.');
                    }
                } elseif (is_null($val) || is_bool($val) || is_numeric($val)) {
                    //Dont need to escape
                } else {
                    throw new Ui_Exception('The specified variable \''.$key.'\' is of type \''.gettype($val).'\'.  Please use either string or array.');
                }
                
                //Make sure the $key is escaped
                $this->escapeVar($key, $key);
                
                //Assign the data back to the view
                $this->_helper->layout->getLayoutInstance()->getView()->assign($key, $val);
            }
        }
    }
    
    private function escapeVar(&$val, $key) {
        //Keep a copy of the original to see if it needed escaping
        $orig = (string) $val; 
        
        //Un-escape the data just in case the data has already been escaped once
        $val  = htmlspecialchars_decode($val, ENT_COMPAT);
        
        //Escape the data
        $val  = $this->view->escape($val);
        
        $config = Zend_Registry::get('config');
        
        if ($config->debug) {
            if ($orig != $val) {
                //For debugging, keep a list of data that needed escaping
                $d = new Zend_Session_Namespace('EscapeDebug');
                
                $d->changes[] = array(
                   'key'      => $this->view->escape($key),
                   'original' => $orig,
                   'escaped'  => $val,
                );
            }
        }
    }
}
