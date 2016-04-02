<?php

//Set the include path
set_include_path(realpath(dirname(__FILE__)));

class Bootstrap {
    public static $config = null;
    
    public static function start($cli=false) {
        self::prepare();
        
        if ($cli === false) {
            self::checkForMaintenanceMode();
        }
        
        self::setErrorHandler();
        self::loadConfig();
        self::setupLocale();
        self::setupLogging();
        
        if ($cli === false) {
            //The following should only be loaded for the website, not for cli scripts
            self::setupSessions();
            self::setupRoutes();
            self::setupView();
            
            //Run the app
            Zend_Controller_Front::run('../application/controllers');
        }
    }
    
    public static function prepare() {
        require_once realpath(dirname(__FILE__).'/Ui/Loader/Autoloader.php');
        
        $a = new Ui_Loader_Autoloader();
        $a->register();
    }
    
    public static function checkForMaintenanceMode() {
        $publicPath = (defined('PUBLIC_PATH')) ? PUBLIC_PATH : '../public';
        $filename   = $publicPath.'/.maintenance';
        
        if (file_exists($filename)) {
            //Website is in maintenance mode
            header('HTTP/1.0 503 Service Unavailable');
            echo 'Bugify is undergoing maintenance';
            exit;
        }
    }
    
    public static function setErrorHandler() {
        set_error_handler(array('Ui_Exception_Handler', 'errorHandlerCallback'));
    }
    
    public static function loadConfig() {
        //Work out the full path to the library folder
        $path = realpath(dirname(__FILE__));
        
        //Load the config
        $config = new Zend_Config(require $path.'/defaults.php', true);

        //Check if a custom config file has been created
        if (file_exists($path.'/config.php')) {
            $custom_config = new Zend_Config(require $path.'/config.php', true);
            
            //Merge the custom config into the defaults
            $config->merge($custom_config);
        } else {
            header('Location: /install');
            exit;
        }
        
        //Work out the base path and save it in the config
        $config->base_path = realpath(dirname(__FILE__).'/../');
        
        //Save the public path
        $config->public_path = (defined('PUBLIC_PATH')) ? PUBLIC_PATH : '../public';
        
        //Set the config as readonly so there can be no further changes
        $config->setReadOnly();
        
        //Save the config data for use with other bootstrap methods
        self::$config = $config;
        
        /**
         * When in debug mode, we log the Zend_* classes.
         * This is used to know which Zend Framework libraries are
         * being used.  The ones that arent can be dropped.
         */
        if ($config->debug == true) {
            Ui_Loader_Autoloader::setDebug(true);
        }
        
        //Store config in the registry for access later
        Zend_Registry::set('config', $config);
    }
    
    public static function setupLocale() {
        setlocale(LC_ALL, self::$config->locale);
        date_default_timezone_set(Bugify_Date::GMT_TIMEZONE);
        
        /**
         * Zend_Locale uses a cache to speed things up.
         * It makes quite a noticeable difference when Zend_Locale
         * has cache disabled.
         */
        $disableCache = true;
        
        if (self::$config->cache->enabled) {
            $cacheDir = self::$config->base_path.self::$config->cache->cache_dir;
            
            if (is_dir($cacheDir) && is_writable($cacheDir)) {
                $frontendOptions = array(
                   'lifetime'                => self::$config->cache->lifetime,
                   'automatic_serialization' => true,
                );
                
                $backendOptions = array(
                   'cache_dir' => $cacheDir,
                );
                
                //Load an instance of standard file cache
                $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
                
                Zend_Locale::setCache($cache);
                
                $disableCache = false;
            }
        }
        
        if ($disableCache) {
            /**
             * If we don't disable caching, it will try and use /tmp which
             * can cause issues on some hosts, and if multiple instances of Bugify
             * are installed on the same server, there could be permission
             * issues accessing the cache files in /tmp
             */
            Zend_Locale::disableCache(true);
        }
    }
    
    public static function setupLogging() {
        $path      = self::$config->base_path.self::$config->logs->path;
        $full_path = $path.'/'.self::$config->logs->filename;
        
        if (!file_exists($full_path)) {
            touch($full_path);
        }
        
        $writer = new Zend_Log_Writer_Stream($full_path);
        $logger = new Zend_Log($writer);
        
        //Store the logger for access later
        Zend_Registry::set('logger', $logger);
    }
    
    public static function setupSessions() {
        if (!file_exists(self::$config->session->path)) {
            //Create the sessions directory
            mkdir(self::$config->session->path);
            
            //Set permissions
            chmod(self::$config->session->path, 0700);
        }
        
        $cookie_secure = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') ? true : false;
        
        //Configure the session
        $options = array(
           'name'            => self::$config->session->name,
           'save_path'       => self::$config->session->path,
           'cookie_secure'   => $cookie_secure,
           'cookie_lifetime' => 0,
           'cookie_httponly' => true,
           'gc_maxlifetime'  => self::$config->session->timeout,
           'gc_divisor'      => self::$config->session->gc_divisor,
           'gc_probability'  => self::$config->session->gc_probability,
        );
        
        Zend_Session::setOptions($options);
        
        //The following code is recommended by Zend to help make XSS harder
        $defaultNamespace = new Zend_Session_Namespace();
        
        if (!isset($defaultNamespace->initialized)) {
            Zend_Session::regenerateId();
            $defaultNamespace->initialized = true;
        }
    }
    
    public static function setupRoutes() {
        //Add required routes for nicer URL formatting
        $router = Zend_Controller_Front::getInstance()->getRouter();
        
        //API - Default (unrouted)
        $route = new Zend_Controller_Router_Route(
            'api/*',
            array(
               'controller' => 'api',
               'action'     => 'error'
            )
        );
        $router->addRoute('api-error', $route);
        
        //API - Index
        $route = new Zend_Controller_Router_Route(
            'api',
            array(
               'controller' => 'api',
               'action'     => 'index'
            )
        );
        $router->addRoute('api-index', $route);
        
        //API - Index (with format)
        $route = new Zend_Controller_Router_Route_Regex(
            'api\.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'index'
            ),
            array(
               1 => 'format',
            )
        );
        $router->addRoute('api-index-with-format', $route);
        
        //API - Issue overview
        $route = new Zend_Controller_Router_Route_Regex(
            'api/issues/(\d+)\.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'overview'
            ),
            array(
               1 => 'issue_id',
               2 => 'format',
            )
        );
        $router->addRoute('api-overview', $route);
        
        //API - Issues - Search
        $route = new Zend_Controller_Router_Route_Regex(
            'api/issues/search\.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'search'
            ),
            array(
               1 => 'format',
            )
        );
        $router->addRoute('api-issues-search', $route);
        
        //API - Issues - Mine
        $route = new Zend_Controller_Router_Route_Regex(
            'api/issues/mine\.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'mine'
            ),
            array(
               1 => 'format',
            )
        );
        $router->addRoute('api-issues-mine', $route);
        
        //API - Issues - Following
        $route = new Zend_Controller_Router_Route_Regex(
            'api/issues/following\.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'following'
            ),
            array(
               1 => 'format',
            )
        );
        $router->addRoute('api-issues-following', $route);
        
        //API - Issues
        $route = new Zend_Controller_Router_Route_Regex(
            'api/issues\.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'issues'
            ),
            array(
               1 => 'format',
            )
        );
        $router->addRoute('api-issues', $route);
        
        //API - Filters
        $route = new Zend_Controller_Router_Route_Regex(
            'api/filters\.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'filters'
            ),
            array(
               1 => 'format',
            )
        );
        $router->addRoute('api-filters', $route);
        
        //API - Filter - Issues
        $route = new Zend_Controller_Router_Route_Regex(
            'api/filters/(\d+)/issues\.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'filter-issues'
            ),
            array(
               1 => 'filter_id',
               2 => 'format',
            )
        );
        $router->addRoute('api-filter-issues', $route);
        
        //API - Users
        $route = new Zend_Controller_Router_Route_Regex(
            'api/users\.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'users'
            ),
            array(
               1 => 'format',
            )
        );
        $router->addRoute('api-users', $route);
        
        //API - Users - Issues
        $route = new Zend_Controller_Router_Route_Regex(
            'api/users/(.*)/issues\.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'users-issues'
            ),
            array(
               1 => 'username',
               2 => 'format',
            )
        );
        $router->addRoute('api-users-issues', $route);
        
        //API - Projects
        $route = new Zend_Controller_Router_Route_Regex(
            'api/projects.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'projects'
            ),
            array(
               1 => 'format',
            )
        );
        $router->addRoute('api-projects', $route);
        
        //API - Project - Issues
        $route = new Zend_Controller_Router_Route_Regex(
            'api/projects/(.*)/issues\.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'project-issues'
            ),
            array(
               1 => 'project_slug',
               2 => 'format',
            )
        );
        $router->addRoute('api-projects-issues', $route);
        
        //API - Milestones
        $route = new Zend_Controller_Router_Route_Regex(
            'api/milestones.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'milestones'
            ),
            array(
               1 => 'format',
            )
        );
        $router->addRoute('api-milestones', $route);
        
        //API - Milestones - Issues
        $route = new Zend_Controller_Router_Route_Regex(
            'api/milestones/(\d+)/issues\.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'milestone-issues'
            ),
            array(
               1 => 'milestone_id',
               2 => 'format',
            )
        );
        $router->addRoute('api-milestones-issues', $route);
        
        //API - History
        $route = new Zend_Controller_Router_Route_Regex(
            'api/history.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'history'
            ),
            array(
               1 => 'format',
            )
        );
        $router->addRoute('api-history', $route);
        
        //API - Get System Info
        $route = new Zend_Controller_Router_Route_Regex(
            'api/system.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'system'
            ),
            array(
               1 => 'format',
            )
        );
        $router->addRoute('api-system', $route);
        
        //API - Auth
        $route = new Zend_Controller_Router_Route_Regex(
            'api/auth.(\w+)',
            array(
               'controller' => 'api',
               'action'     => 'auth'
            ),
            array(
               1 => 'format',
            )
        );
        $router->addRoute('api-auth', $route);
        
        
        //Login
        $route = new Zend_Controller_Router_Route_Static(
            'login',
            array(
               'controller' => 'auth',
               'action'     => 'login'
            )
        );
        $router->addRoute('login', $route);
        
        //Logout
        $route = new Zend_Controller_Router_Route_Static(
            'logout',
            array(
               'controller' => 'auth',
               'action'     => 'logout'
            )
        );
        $router->addRoute('logout', $route);
        
        //Add first user
        $route = new Zend_Controller_Router_Route_Static(
            'add-user',
            array(
               'controller' => 'auth',
               'action'     => 'add-user'
            )
        );
        $router->addRoute('add-user', $route);
        
        //Delete a user
        $route = new Zend_Controller_Router_Route(
            'users/:username/delete',
            array(
               'controller' => 'users',
               'action'     => 'delete'
            )
        );
        $router->addRoute('delete-user', $route);
        
        //Gravatar images
        $route = new Zend_Controller_Router_Route(
            'assets/gravatar/:size/:email',
            array(
               'controller' => 'assets',
               'action'     => 'gravatar'
            ),
            array(
               'size' => '\d+',
            )
        );
        $router->addRoute('gravatar', $route);
        
        //Chart images
        $route = new Zend_Controller_Router_Route(
            'assets/chart/:name/:width/:height',
            array(
               'controller' => 'assets',
               'action'     => 'chart',
            ),
            array(
               'width'  => '\d+',
               'height' => '\d+',
            )
        );
        $router->addRoute('chart', $route);
        
        //Issue attachments
        $route = new Zend_Controller_Router_Route(
            'assets/attachment/:issue_id/:filename',
            array(
               'controller' => 'assets',
               'action'     => 'attachment'
            ),
            array(
               'issue_id' => '\d+',
            )
        );
        $router->addRoute('attachments', $route);
        
        //Issue overview
        $route = new Zend_Controller_Router_Route(
            'issues/:issue_id',
            array(
               'controller' => 'issues',
               'action'     => 'overview'
            ),
            array(
               'issue_id' => '\d+',
            )
        );
        $router->addRoute('issue-overview', $route);
        
        //Edit issue
        $route = new Zend_Controller_Router_Route(
            'issues/:issue_id/edit',
            array(
               'controller' => 'issues',
               'action'     => 'edit'
            ),
            array(
               'issue_id' => '\d+',
            )
        );
        $router->addRoute('issue-edit', $route);
        
        //Project overview
        $route = new Zend_Controller_Router_Route(
            'projects/:project_slug',
            array(
               'controller' => 'projects',
               'action'     => 'overview'
            )
        );
        $router->addRoute('projects-overview', $route);
        
        //New category for project
        $route = new Zend_Controller_Router_Route(
            'projects/:project_slug/new-category',
            array(
               'controller' => 'projects',
               'action'     => 'new-category'
            )
        );
        $router->addRoute('projects-new-category', $route);
        
        //Project category issues
        $route = new Zend_Controller_Router_Route(
            'projects/:project_slug/categories/:category_id',
            array(
               'controller' => 'projects',
               'action'     => 'category-issues'
            ),
            array(
               'category_id' => '\d+',
            )
        );
        $router->addRoute('projects-category-issues', $route);
        
        //Delete a project
        $route = new Zend_Controller_Router_Route(
            'projects/:project_slug/delete',
            array(
               'controller' => 'projects',
               'action'     => 'delete'
            )
        );
        $router->addRoute('delete-project', $route);
        
        //Delete a project category
        $route = new Zend_Controller_Router_Route(
            'projects/:project_slug/categories/:category_id/delete',
            array(
               'controller' => 'projects',
               'action'     => 'delete-category'
            ),
            array(
               'category_id' => '\d+',
            )
        );
        $router->addRoute('delete-project-category', $route);
        
        //Milestone overview
        $route = new Zend_Controller_Router_Route(
            'milestones/:milestone_id',
            array(
               'controller' => 'milestones',
               'action'     => 'overview'
            ),
            array(
               'milestone_id' => '\d+',
            )
        );
        $router->addRoute('milestones-overview', $route);
        
        //Users' issues
        $route = new Zend_Controller_Router_Route(
            'users/:username/issues',
            array(
               'controller' => 'users',
               'action'     => 'issues'
            )
        );
        $router->addRoute('user-issues', $route);
        
        //Edit a user
        $route = new Zend_Controller_Router_Route(
            'users/:username/edit',
            array(
               'controller' => 'users',
               'action'     => 'edit'
            )
        );
        $router->addRoute('edit-user', $route);
        
        //Delete a user
        $route = new Zend_Controller_Router_Route(
            'users/:username/delete',
            array(
               'controller' => 'users',
               'action'     => 'delete'
            )
        );
        $router->addRoute('delete-user', $route);
    }
    
    public static function setupView() {
        //Load the layout engine (templates)
        $layout = Zend_Layout::startMvc(self::$config->layout);
        $view = Zend_Layout::getMvcInstance()->getView();
        $view->doctype('XHTML1_STRICT');
        $view->setEncoding('UTF-8');
        
        $view->headMeta()->appendHttpEquiv('Content-Type', 'text/html;charset=utf-8');
        
        Zend_Registry::set('layout', $layout);
    }
}
