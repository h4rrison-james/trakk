<?php

class ApiController extends Ui_Controller_Action
{
    //Auth'd user
    private $_api_user = null;
    
    public function init()
    {
        //Disable layouts because everything is echo'd
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        //Make sure the user is auth'd
        $this->_checkAuth();
    }
    
    private function _checkAuth()
    {
        try
        {
            $action = $this->getRequest()->getActionName();
            
            //The following actions dont need auth
            $non_auth_actions = array(
               'auth',
               'system',
            );
            
            if (!in_array($action, $non_auth_actions))
            {
                //Check if the api_key param was specified (overrides basic auth)
                if (strlen($this->_getParam('api_key')) > 0)
                {
                    //Use the api_key param
                    $api_key = $this->_getParam('api_key');
                }
                else
                {
                    //Get the basic auth username (Api key)
                    $api_key = (isset($_SERVER['PHP_AUTH_USER'])) ? $_SERVER['PHP_AUTH_USER'] : '';
                }
                
                if (strlen($api_key) > 0)
                {
                    //Check which user this Api key belongs to
                    $u = new Bugify_Users();
                    $this->_api_user = $u->fetchApiUser($api_key);
                }
                else
                {
                    throw new Ui_Exception('Please specify the Api key.', 401);
                }
            }
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    private function _getDefaultLimit()
    {
        return 20;
    }
    
    private function _getFormat()
    {
        //Work out the requested data format
        $format = $this->_getParam('format');
        $format = (strlen($format) > 0) ? $format : 'json';
        
        $valid_formats = array(
           'json',
           'jsonp',
           'xml',
           'txt',
           'php',
           'wddx',
           'pickle',
           'yml',
        );
        
        if (!in_array($format, $valid_formats))
        {
            throw new Ui_Exception(sprintf('Unknown format requested. Please use one of: %s', implode(', ', $valid_formats)));
        }
        
        return $format;
    }
    
    private function _formatResult($data) {
        //Make sure the data is an array
        if (!is_array($data)) {
            throw new Ui_Exception('Data must be provided as an array.');
        }
        
        //Work out the requested data format
        $format = $this->_getFormat();
        
        switch ($format) {
            case 'json':
                $content_type = 'application/json';
                $data         = json_encode($data);
                break;
            case 'jsonp':
                $content_type = 'application/javascript';
                
                //Prepend the callback param
                $callback = (strlen($this->_getParam('callback')) > 0) ? $this->_getParam('callback') : 'callback';
                $data     = sprintf('%s(%s);', $callback, json_encode($data));
                break;
            case 'xml':
                $content_type = 'application/xml';
                $data         = $this->_writeXml($data);
                break;
            case 'txt':
                $content_type = 'text/plain';
                $data         = print_r($data, true);
                break;
            case 'php':
                $content_type = 'text/html';
                $data         = serialize($data);
                break;
            case 'wddx':
                $content_type = 'text/xml';
                $serializer   = Zend_Serializer::factory('Wddx');
                $data         = $serializer->serialize($data);
                break;
            case 'pickle':
                $content_type = 'application/x-python-serialize';
                $serializer   = Zend_Serializer::factory('PythonPickle');
                $data         = $serializer->serialize($data);
                break;
            case 'yml':
                $content_type = 'application/x-yaml';
                
                //Load the data into a zend config object
                $c = new Zend_Config($data);
                
                //Create a YAML config writer
                $w = new Zend_Config_Writer_Yaml();
                $w->setConfig($c);
                
                //Rendering the config file generates the YAML format
                $data = $w->render();
                break;
        }
        
        //Set the correct content-type header
        $this->getResponse()->setHeader('Content-type', $content_type);
        
        return $data;
    }
    
    private function _writeXml($data)
    {
        $xml = new XmlWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('root');
        
        $this->_writeXmlElement($xml, $data);
        
        $xml->endElement();
        
        return $xml->outputMemory(true);
    }
    
    private function _writeXmlElement(XMLWriter $xml, $data)
    {
        foreach ($data as $key => $value)
        {
            if (is_array($value))
            {
                //Cannot have integer keys in xml
                $key = (ctype_digit((string)$key) === true) ? 'key_'.$key : $key;
                
                $xml->startElement($key);
                
                $this->_writeXmlElement($xml, $value);
                
                $xml->endElement();
                
                continue;
            }
            
            $xml->writeElement($key, $value);
        }
    }
    
    private function _handleException(Exception $e) {
        if (!$e instanceof Exception) {
            //Create an exception so we can carry on with this error handling
            try {
                throw new Ui_Exception('Unknown error');
            }
            catch (Exception $e) {}
        }
        
        if ($e->getCode() == 404) {
            $this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
        } elseif ($e->getCode() == 401) {
            if (!isset($_SERVER['HTTP_X_PREVENT_BASIC'])) {
                /**
                 * Output the headers for basic auth.
                 * The order of the headers is important.
                 */
                header('WWW-Authenticate: Basic realm="Bugify API Authentication"');
                header('HTTP/1.0 401 Unauthorized');
            } else {
                /**
                 * Dont use the correct basic authentication header.
                 * This is useful for API clients that don't want to let the browser
                 * popup to authenticate.  The Bugify mobile app uses this.
                 */
                header('WWW-Authenticate: XBasic');
                header('HTTP/1.0 401 Unauthorized');
            }
            
            //The following text is displayed if the user clicks cancel in the login box
            echo 'Please provide your Api key as the username with anything as the password.';
            exit;
        } else {
            $this->getResponse()->setRawHeader('HTTP/1.1 500 Internal Server Error');
        }
        
        $data = array(
           'error' => array(
              'code'    => $e->getCode(),
              'message' => $e->getMessage(),
           ),
        );
        
        echo $this->_formatResult($data);
    }
    
    public function errorAction()
    {
        try
        {
            throw new Ui_Exception('Invalid Api Uri.', 404);
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    public function authAction()
    {
        try
        {
            if ($this->getRequest()->isPost())
            {
                $username = $this->_getParam('username');
                $password = $this->_getParam('password');
                
                if (strlen($username) > 0 && strlen($password) > 0)
                {
                    $a    = new Bugify_Auth();
                    $user = $a->auth($username, $password);
                    
                    if ($user !== false)
                    {
                        $data = array(
                           'user'    => $user->toArray(),
                           'api_key' => $user->getApiKey(),
                        );
                        
                        echo $this->_formatResult($data);
                    }
                }
                else
                {
                    throw new Ui_Exception('Please type both your username and password.', 400);
                }
            }
            else
            {
                throw new Ui_Exception('Auth must be POSTed', 400);
            }
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    public function systemAction()
    {
        try
        {
            $data = array(
               'hostname' => Bugify_Host::getHostname(),
               'version'  => Bugify_Version::VERSION,
            );
            
            echo $this->_formatResult($data);
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    public function indexAction()
    {
        $data = array(
            'uris' => array(
               array(
                  'name' => 'All Issues',
                  'path' => sprintf('/issues.%s', $this->_getFormat()),
               ),
               array(
                  'name' => 'Search',
                  'path' => sprintf('/issues/search.%s', $this->_getFormat()),
               ),
               array(
                  'name' => 'My Issues',
                  'path' => sprintf('/issues/mine.%s', $this->_getFormat()),
               ),
               array(
                  'name' => 'Filters',
                  'path' => sprintf('/filters.%s', $this->_getFormat()),
               ),
               array(
                  'name' => 'Following',
                  'path' => sprintf('/issues/following.%s', $this->_getFormat()),
               ),
               array(
                  'name' => 'Projects',
                  'path' => sprintf('/projects.%s', $this->_getFormat()),
               ),
               array(
                  'name' => 'Milestones',
                  'path' => sprintf('/milestones.%s', $this->_getFormat()),
               ),
               array(
                  'name' => 'History',
                  'path' => sprintf('/history.%s', $this->_getFormat()),
               ),
               array(
                  'name' => 'Users',
                  'path' => sprintf('/users.%s', $this->_getFormat()),
               ),
               array(
                  'name' => 'System Info',
                  'path' => sprintf('/system.%s', $this->_getFormat()),
               ),
            ),
        );
        
        echo $this->_formatResult($data);
    }
    
    public function issuesAction() {
        try {
            $page   = (int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1;
            $limit  = (int)$this->_getParam('limit') > 0 ? $this->_getParam('limit') : $this->_getDefaultLimit();
            $total  = 0;
            
            if (!$this->getRequest()->isPost()) {
                $data = array(
                   'total'  => $total,
                   'page'   => $page,
                   'limit'  => $limit,
                   'issues' => array(),
                );
                
                //Load all open issues
                $i = new Bugify_Issues();
                $i->setPaginationPage($page);
                $i->setPaginationLimit($limit);
                
                //Set the filter options and fetch the issues
                $filter = $i->filter();
                $filter->setStates($i->getOpenStates());
                $result = $i->fetchAll($filter);
                
                //Process the issues (ie, attach extra info)
                $h = new Bugify_Helpers_Issues();
                $issues = $h->processIssues($result);
                
                /**
                 * We don't include the comments or history in this list,
                 * so remove the parameters.
                 */
                if (is_array($issues) && count($issues) > 0) {
                    foreach ($issues as $key => $val) {
                        unset($issues[$key]['attachments']);
                        unset($issues[$key]['comments']);
                        unset($issues[$key]['followers']);
                        unset($issues[$key]['history']);
                    }
                }
                
                //Get the pagination info
                $page  = $i->getPaginationPage();
                $limit = $i->getPaginationLimit();
                $total = $i->getTotal();
                
                $data = array(
                   'total'  => $total,
                   'page'   => $page,
                   'limit'  => $limit,
                   'issues' => $issues,
                );
            } else {
                /**
                 * Create a new issue.
                 */
                $issue        = $this->_getAllParams();
                $project_id   = (isset($issue['project'])) ? $issue['project'] : 0;
                $category_id  = (isset($issue['category'])) ? $issue['category'] : 0;
                $milestone_id = (isset($issue['milestone'])) ? $issue['milestone'] : 0;
                $assignee_id  = (isset($issue['assignee'])) ? $issue['assignee'] : 0;
                $subject      = (isset($issue['subject'])) ? $issue['subject'] : '';
                $description  = (isset($issue['description'])) ? $issue['description'] : '';
                $priority     = (isset($issue['priority'])) ? $issue['priority'] : Bugify_Issue::PRIORITY_NORMAL;
                
                //Create a new issue
                $new = new Bugify_Issue();
                $new->setProjectId($project_id)
                    ->setCategoryId($category_id)
                    ->setMilestoneId($milestone_id)
                    ->setCreatorId($this->_api_user->getUserId())
                    ->setAssigneeId($assignee_id)
                    ->setSubject($subject)
                    ->setDescription($description)
                    ->setPriority($priority)
                    ->setState(Bugify_Issue::STATE_OPEN);
                
                //Save the issue
                $i  = new Bugify_Issues();
                $id = $i->save($new, $this->_api_user);
                
                //Reload the issue
                $issue = $i->fetch($id);
                
                //Clear issue counts
                $this->cache->removeWithTags('IssueCount');
                
                //Add to search index
                $this->search->addIssueDocument($issue);
                
                $data = array(
                   'total'    => $total,
                   'page'     => $page,
                   'limit'    => $limit,
                   'issues'   => array(),
                   'message'  => 'Issue has been created.',
                   'issue_id' => $id,
                );
                
                //Set the HTTP response code 201 (created)
                $this->getResponse()->setRawHeader('HTTP/1.1 201 Created');
            }
            
            echo $this->_formatResult($data);
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    public function mineAction()
    {
        try
        {
            $page   = (int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1;
            $limit  = (int)$this->_getParam('limit') > 0 ? $this->_getParam('limit') : $this->_getDefaultLimit();
            $total  = 0;
            
            $data = array(
               'total'  => $total,
               'page'   => $page,
               'limit'  => $limit,
               'issues' => array(),
            );
            
            //Load all open issues
            $i = new Bugify_Issues();
            $i->setPaginationPage($page);
            $i->setPaginationLimit($limit);
            
            //Set the filter options and fetch the issues
            $filter = $i->filter();
            $filter->setAssigneeIds(array($this->_api_user->getUserId()));
            $result = $i->fetchAll($filter);
            
            //Process the issues (ie, attach extra info)
            $h = new Bugify_Helpers_Issues();
            $issues = $h->processIssues($result);
            
            /**
             * We don't include the comments or history in this list,
             * so remove the parameters.
             */
            if (is_array($issues) && count($issues) > 0) {
                foreach ($issues as $key => $val) {
                    unset($issues[$key]['attachments']);
                    unset($issues[$key]['comments']);
                    unset($issues[$key]['followers']);
                    unset($issues[$key]['history']);
                }
            }
            
            //Get the pagination info
            $page  = $i->getPaginationPage();
            $limit = $i->getPaginationLimit();
            $total = $i->getTotal();
            
            $data = array(
               'total'  => $total,
               'page'   => $page,
               'limit'  => $limit,
               'issues' => $issues,
            );
            
            echo $this->_formatResult($data);
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    public function searchAction()
    {
        try
        {
            $page   = (int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1;
            $limit  = (int)$this->_getParam('limit') > 0 ? $this->_getParam('limit') : $this->_getDefaultLimit();
            $total  = 0;
            
            $data = array(
               'total'  => $total,
               'page'   => $page,
               'limit'  => $limit,
               'issues' => array(),
            );
            
            $search_string = $this->_getParam('q');
            
            if (strlen($search_string) > 0)
            {
                //Open the search index
                $this->search->setPaginationPage($page);
                $this->search->setPaginationLimit($limit);
                
                $results = $this->search->searchIndex($search_string);
                
                if (is_array($results) && count($results) > 0)
                {
                    $issue_ids = array();
                    
                    foreach ($results as $key => $val)
                    {
                        $issue_ids[] = $val['issue_id'];
                    }
                    
                    //Now load these issues to get the full details
                    $i = new Bugify_Issues();
                    
                    $filter = $i->filter();
                    $filter->setIssueIds($issue_ids);
                    $filter->setStates(array(
                       Bugify_Issue::STATE_OPEN,
                       Bugify_Issue::STATE_IN_PROGRESS,
                       Bugify_Issue::STATE_RESOLVED,
                       Bugify_Issue::STATE_CLOSED,
                       Bugify_Issue::STATE_REOPENED,
                    ));
                    
                    $result = $i->fetchAll($filter);
                    
                    //Process the issues (ie, attach extra info)
                    $h = new Bugify_Helpers_Issues();
                    $issues = $h->processIssues($result);
                    
                    /**
                     * We don't include the comments or history in this list,
                     * so remove the parameters.
                     */
                    if (is_array($issues) && count($issues) > 0) {
                        foreach ($issues as $key => $val) {
                            unset($issues[$key]['attachments']);
                            unset($issues[$key]['comments']);
                            unset($issues[$key]['followers']);
                            unset($issues[$key]['history']);
                        }
                    }
                    
                    //todo - need to order these issues by the scoring that lucene gave us
                    
                    //Get the pagination info
                    $page  = $this->search->getPaginationPage();
                    $limit = $this->search->getPaginationLimit();
                    $total = $this->search->getTotal();
                    
                    $data = array(
                       'total'  => $total,
                       'page'   => $page,
                       'limit'  => $limit,
                       'issues' => $issues,
                    );
                }
            }
            
            echo $this->_formatResult($data);
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    public function filtersAction()
    {
        try
        {
            $page   = (int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1;
            $limit  = (int)$this->_getParam('limit') > 0 ? $this->_getParam('limit') : $this->_getDefaultLimit();
            $total  = 0;
            
            $data = array(
               'total'   => $total,
               'page'    => $page,
               'limit'   => $limit,
               'filters' => array(),
            );
            
            //Load the saved filters for this user
            $f = new Bugify_Filters();
            $result  = $f->fetchAllForUser($this->_api_user);
            $filters = array();
            
            foreach ($result as $filter)
            {
                $filters[] = $filter->toArray();
            }
            
            $data = array(
               'total'   => count($filters),
               'page'    => $page,
               'limit'   => $limit,
               'filters' => $filters,
            );
            
            echo $this->_formatResult($data);
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    public function filterIssuesAction()
    {
        try
        {
            $page   = (int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1;
            $limit  = (int)$this->_getParam('limit') > 0 ? $this->_getParam('limit') : $this->_getDefaultLimit();
            $total  = 0;
            
            $data = array(
               'total'  => $total,
               'page'   => $page,
               'limit'  => $limit,
               'issues' => array(),
            );
            
            $filter_id = (int)$this->_getParam('filter_id');
            
            //Prepare to load the issues for this filter
            $i = new Bugify_Issues();
            $i->setPaginationPage($page);
            $i->setPaginationLimit($limit);
            
            /**
             * Prepare the default filter options.
             * By default we want all open issues that are
             * assigned to the logged in user.
             */
            $filter = array(
               'search'     => '',
               'projects'   => array(),
               'priorities' => array(),
               'states'     => $i->getOpenStates(),
               'milestones' => array(),
               'users'      => array(),
            );
            
            //Load the filter
            $f = new Bugify_Filters();
            $result = $f->fetch($filter_id);
            
            /**
             * Make sure this filter is for the logged in user.
             * todo - how important is this check?
             */
            if ($result->getUserId() != $this->_api_user->getUserId())
            {
                throw new Bugify_Exception('The specified filter belongs to another user.');
            }
            
            //Set the filter options
            $filter['search']     = $result->getSearch();
            $filter['projects']   = $result->getProjectIds();
            $filter['priorities'] = $result->getPriorities();
            $filter['states']     = $result->getStates();
            $filter['milestones'] = $result->getMilestoneIds();
            $filter['users']      = $result->getAssigneeIds();
            
            //Set the filter options
            $f = $i->filter();
            
            //Check for a search string
            if (strlen($filter['search']) > 0)
            {
                /**
                 * Do a lucene search first, then use the resulting issue_id's for
                 * the rest of the filtering.
                 */
                $this->search->setPaginationLimit(100);
                
                $results = $this->search->searchIndex($filter['search']);
                
                if (is_array($results) && count($results) > 0)
                {
                    $issue_ids = array();
                    
                    foreach ($results as $key => $val)
                    {
                        $issue_ids[] = $val['issue_id'];
                    }
                    
                    $f->setSearch($filter['search'])
                      ->setIssueIds($issue_ids);
                }
            }
            
            foreach ($filter as $key => $val)
            {
                switch ($key)
                {
                    case 'projects':
                        $f->setProjectIds($val);
                        break;
                    case 'priorities':
                        $f->setPriorities($val);
                        break;
                    case 'states':
                        $f->setStates($val);
                        break;
                    case 'milestones':
                        $f->setMilestoneIds($val);
                        break;
                    case 'users':
                        $f->setAssigneeIds($val);
                        break;
                }
            }
            
            //Fetch the issues
            $result = $i->fetchAll($f);
            
            //Process the issues (ie, attach extra info)
            $h = new Bugify_Helpers_Issues();
            $issues = $h->processIssues($result);
            
            /**
             * We don't include the comments or history in this list,
             * so remove the parameters.
             */
            if (is_array($issues) && count($issues) > 0) {
                foreach ($issues as $key => $val) {
                    unset($issues[$key]['attachments']);
                    unset($issues[$key]['comments']);
                    unset($issues[$key]['followers']);
                    unset($issues[$key]['history']);
                }
            }
            
            //Get the pagination info
            $page  = $i->getPaginationPage();
            $limit = $i->getPaginationLimit();
            $total = $i->getTotal();
            
            $data = array(
               'total'  => count($issues),
               'page'   => $page,
               'limit'  => $limit,
               'issues' => $issues,
            );
            
            echo $this->_formatResult($data);
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    public function followingAction()
    {
        try
        {
            $page   = (int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1;
            $limit  = (int)$this->_getParam('limit') > 0 ? $this->_getParam('limit') : $this->_getDefaultLimit();
            $total  = 0;
            
            $data = array(
               'total'  => $total,
               'page'   => $page,
               'limit'  => $limit,
               'issues' => array(),
            );
            
            //Load all open issues
            $i = new Bugify_Issues();
            $i->setPaginationPage($page);
            $i->setPaginationLimit($limit);
            
            //Fetch the follows by this user
            $follows   = $i->fetchFollowsByUser($this->_api_user);
            $issue_ids = array();
            
            foreach ($follows as $follower)
            {
                $issue_ids[] = $follower->getIssueId();
            }
            
            //Set the filter options and fetch the issues
            $filter = $i->filter();
            $filter->setStates($i->getAllStates())
                   ->setIssueIds($issue_ids);
            $result = $i->fetchAll($filter);
            
            //Process the issues (ie, attach extra info)
            $h = new Bugify_Helpers_Issues();
            $issues = $h->processIssues($result);
            
            /**
             * We don't include the comments or history in this list,
             * so remove the parameters.
             */
            if (is_array($issues) && count($issues) > 0) {
                foreach ($issues as $key => $val) {
                    unset($issues[$key]['attachments']);
                    unset($issues[$key]['comments']);
                    unset($issues[$key]['followers']);
                    unset($issues[$key]['history']);
                }
            }
            
            //Get the pagination info
            $page  = $i->getPaginationPage();
            $limit = $i->getPaginationLimit();
            $total = $i->getTotal();
            
            $data = array(
               'total'  => $total,
               'page'   => $page,
               'limit'  => $limit,
               'issues' => $issues,
            );
            
            echo $this->_formatResult($data);
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    public function overviewAction()
    {
        try
        {
            $data = array(
               'issue' => array(),
            );
            
            $issue_id = $this->_getParam('issue_id');
            
            //Load the issue details
            $i = new Bugify_Issues();
            $issue = $i->fetch($issue_id);
            
            if (!$this->getRequest()->isPost()) {
                /**
                 * Fetch an issue.
                 */
                $params             = $this->_getAllParams();
                $includeAttachments = (isset($params['attachments']) && $params['attachments'] == 'true') ? true : false;
                $includeComments    = (isset($params['comments']) && $params['comments'] == 'true') ? true : false;
                $includeFollowers   = (isset($params['followers']) && $params['followers'] == 'true') ? true : false;
                $includeHistory     = (isset($params['history']) && $params['history'] == 'true') ? true : false;
                
                if ($includeAttachments === true) {
                    //Load attachment info
                    $issue->getAttachments();
                }
                if ($includeComments === true) {
                    //Load comments
                    $issue->getComments();
                }
                if ($includeFollowers === true) {
                    //Load the followers
                    $issue->getFollowers();
                }
                if ($includeHistory === true) {
                    //Load the history
                    $history = $issue->getHistory();
                }
                
                //Process the issue (ie, attach extra info)
                $h = new Bugify_Helpers_Issues();
                $issueArray = $h->processIssues(array($issue));
                $issueArray = current($issueArray);
                
                if ($includeAttachments === false) {
                    unset($issueArray['attachments']);
                }
                if ($includeComments === false) {
                    unset($issueArray['comments']);
                }
                if ($includeFollowers === false) {
                    unset($issueArray['followers']);
                }
                if ($includeHistory === false) {
                    unset($issueArray['history']);
                }
                
                $data = array(
                   'total'  => 1,
                   'page'   => 1,
                   'limit'  => 1,
                   'issues' => array($issueArray),
                );
            } else {
                /**
                 * Update an issue.
                 */
                $params  = $this->_getAllParams();
                $method  = (isset($params['method'])) ? $params['method'] : '';
                $message = '';
                
                switch ($method) {
                    case 'update':
                        //Update the issue
                        if (isset($params['issue'])) {
                            $update = $params['issue'];
                        } else {
                            throw new Bugify_Exception('Please specify the issue details.');
                        }
                        
                        //Update the issue
                        if (isset($update['project'])) {
                            $issue->setProjectId($update['project']);
                        }
                        if (isset($update['category'])) {
                            $issue->setCategoryId($update['category']);
                        }
                        if (isset($update['milestone'])) {
                            $issue->setMilestoneId($update['milestone']);
                        }
                        if (isset($update['assignee'])) {
                            $issue->setAssigneeId($update['assignee']);
                        }
                        if (isset($update['subject'])) {
                            $issue->setSubject($update['subject']);
                        }
                        if (isset($update['description'])) {
                            $issue->setDescription($update['description']);
                        }
                        if (isset($update['priority'])) {
                            $issue->setPriority($update['priority']);
                        }
                        if (isset($update['percentage'])) {
                            $issue->setPercentage($update['percentage']);
                        }
                        if (isset($update['state'])) {
                            $issue->setState($update['state']);
                        }
                        
                        //Save the issue
                        $i  = new Bugify_Issues();
                        $id = $i->save($issue, $this->_api_user);
                        
                        //Clear issue counts
                        $this->cache->removeWithTags('IssueCount');
                        
                        //Update search index
                        $this->search->updateIssueDocument($issue);
                        
                        $message = 'Issue has been updated.';
                        break;
                    case 'addcomment':
                        //Add a new comment
                        if (isset($params['comment'])) {
                            $comment = $params['comment'];
                        } else {
                            throw new Bugify_Exception('Please specify the comment.');
                        }
                        
                        if (strlen($comment) > 0) {
                            //Create a new comment
                            $c = new Bugify_Issue_Comment();
                            $c->setUserId($this->_api_user->getUserId())
                              ->setComment($comment)
                              ->setState(Bugify_Issue_Comment::STATE_ACTIVE);
                            
                            //Save the comment
                            $issue->saveComment($c);
                            
                            //Update search index
                            $this->search->updateIssueDocument($issue);
                        }
                        
                        $message = 'Comment has been added.';
                        break;
                    case 'updatecomment':
                        //Update a comment
                        if (isset($params['comment'])) {
                            $update = $params['comment'];
                        } else {
                            throw new Bugify_Exception('Please specify the comment details.');
                        }
                        
                        if (isset($update['comment']) && isset($update['id'])) {
                            //Find the comment
                            $comments = $issue->getComments();
                            $found    = false;
                            
                            foreach ($comments as $comment) {
                                if ($comment->getCommentId() == $update['id']) {
                                    //This is the issue
                                    $found = true;
                                    break;
                                }
                            }
                            
                            if ($found === false) {
                                throw new Bugify_Exception('The specified comment cannot be found.');
                            }
                            
                            //Edit the comment
                            $comment->setComment($update['comment']);
                            
                            //Save the comment
                            $issue->saveComment($comment);
                            
                            //Update search index
                            $this->search->updateIssueDocument($issue);
                        }
                        
                        $message = 'Comment has been updated.';
                        break;
                    default:
                        throw new Bugify_Exception('Please specify the method you would like to use. (e.g., "update", "addcomment", "updatecomment")');
                }
                
                $data = array(
                    'total'   => 0,
                    'page'    => 1,
                    'limit'   => 1,
                    'issues'  => array(),
                    'message' => $message,
                );
            }
            
            echo $this->_formatResult($data);
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    public function usersAction()
    {
        try
        {
            $page   = (int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1;
            $limit  = (int)$this->_getParam('limit') > 0 ? $this->_getParam('limit') : $this->_getDefaultLimit();
            $total  = 0;
            
            $data = array(
               'total' => $total,
               'page'  => $page,
               'limit' => $limit,
               'users' => array(),
            );
            
            //Fetch all users
            $u = new Bugify_Users();
            $result = $u->fetchAll();
            
            //Process the users (attach issue counts etc)
            $h = new Bugify_Helpers_Issues();
            $users = $h->addIssueCountsForAssignees($result);
            
            $data = array(
               'total' => count($users),
               'page'  => $page,
               'limit' => $limit,
               'users' => $users,
            );
            
            echo $this->_formatResult($data);
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    public function usersIssuesAction()
    {
        try
        {
            $page   = (int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1;
            $limit  = (int)$this->_getParam('limit') > 0 ? $this->_getParam('limit') : $this->_getDefaultLimit();
            $total  = 0;
            
            $data = array(
               'total'  => $total,
               'page'   => $page,
               'limit'  => $limit,
               'issues' => array(),
            );
            
            $username = $this->_getParam('username');
            
            //Load the user details
            $u = new Bugify_Users();
            $result = $u->fetch($username);
            
            //Load all issues for the user
            $i = new Bugify_Issues();
            $i->setPaginationPage($page);
            $i->setPaginationLimit($limit);
            
            //Set the filter options and fetch the issues
            $filter = $i->filter();
            $filter->setAssigneeIds(array($result->getUserId()));
            $result = $i->fetchAll($filter);
            
            //Process the issues (ie, attach extra info)
            $h = new Bugify_Helpers_Issues();
            $issues = $h->processIssues($result);
            
            /**
             * We don't include the comments or history in this list,
             * so remove the parameters.
             */
            if (is_array($issues) && count($issues) > 0) {
                foreach ($issues as $key => $val) {
                    unset($issues[$key]['attachments']);
                    unset($issues[$key]['comments']);
                    unset($issues[$key]['followers']);
                    unset($issues[$key]['history']);
                }
            }
            
            //Get the pagination info
            $page  = $i->getPaginationPage();
            $limit = $i->getPaginationLimit();
            $total = $i->getTotal();
            
            $data = array(
               'total'  => $total,
               'page'   => $page,
               'limit'  => $limit,
               'issues' => $issues,
            );
            
            echo $this->_formatResult($data);
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    public function projectsAction()
    {
        try
        {
            $page   = (int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1;
            $limit  = (int)$this->_getParam('limit') > 0 ? $this->_getParam('limit') : $this->_getDefaultLimit();
            $total  = 0;
            
            $data = array(
               'total'    => $total,
               'page'     => $page,
               'limit'    => $limit,
               'projects' => array(),
            );
            
            //Fetch all projects
            $p = new Bugify_Projects();
            $result = $p->fetchAll();
            
            //We want to include the categories here
            foreach ($result as $project)
            {
                $project->getCategories();
            }
            
            //Process the projects (attach issue counts etc)
            $h = new Bugify_Helpers_Issues();
            $projects = $h->addIssueCountsForProjects($result);
            
            $data = array(
               'total'    => count($projects),
               'page'     => $page,
               'limit'    => $limit,
               'projects' => $projects,
            );
            
            echo $this->_formatResult($data);
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    public function projectIssuesAction()
    {
        try
        {
            $page   = (int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1;
            $limit  = (int)$this->_getParam('limit') > 0 ? $this->_getParam('limit') : $this->_getDefaultLimit();
            $total  = 0;
            
            $data = array(
               'total'  => $total,
               'page'   => $page,
               'limit'  => $limit,
               'issues' => array(),
            );
            
            $project_slug = $this->_getParam('project_slug');
            
            //Load the project details
            $p = new Bugify_Projects();
            $result = $p->fetch($project_slug);
            
            //Load all issues for the project
            $i = new Bugify_Issues();
            $i->setPaginationPage($page);
            $i->setPaginationLimit($limit);
            
            //Set the filter options and fetch the issues
            $filter = $i->filter();
            $filter->setProjectIds(array($result->getProjectId()));
            $result = $i->fetchAll($filter);
            
            //Process the issues (ie, attach extra info)
            $h = new Bugify_Helpers_Issues();
            $issues = $h->processIssues($result);
            
            /**
             * We don't include the comments or history in this list,
             * so remove the parameters.
             */
            if (is_array($issues) && count($issues) > 0) {
                foreach ($issues as $key => $val) {
                    unset($issues[$key]['attachments']);
                    unset($issues[$key]['comments']);
                    unset($issues[$key]['followers']);
                    unset($issues[$key]['history']);
                }
            }
            
            //Get the pagination info
            $page  = $i->getPaginationPage();
            $limit = $i->getPaginationLimit();
            $total = $i->getTotal();
            
            $data = array(
               'total'  => $total,
               'page'   => $page,
               'limit'  => $limit,
               'issues' => $issues,
            );
            
            echo $this->_formatResult($data);
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    public function milestonesAction()
    {
        try
        {
            $page   = (int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1;
            $limit  = (int)$this->_getParam('limit') > 0 ? $this->_getParam('limit') : $this->_getDefaultLimit();
            $total  = 0;
            
            $data = array(
               'total'      => $total,
               'page'       => $page,
               'limit'      => $limit,
               'milestones' => array(),
            );
            
            //Fetch all milestones
            $p = new Bugify_Milestones();
            $result = $p->fetchAll();
            
            //Process the milestones (attach issue counts etc)
            $h = new Bugify_Helpers_Issues();
            $milestones = $h->addIssueCountsForMilestones($result);
            
            $data = array(
               'total'      => count($milestones),
               'page'       => $page,
               'limit'      => $limit,
               'milestones' => $milestones,
            );
            
            echo $this->_formatResult($data);
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    public function milestoneIssuesAction()
    {
        try
        {
            $page   = (int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1;
            $limit  = (int)$this->_getParam('limit') > 0 ? $this->_getParam('limit') : $this->_getDefaultLimit();
            $total  = 0;
            
            $data = array(
               'total'  => $total,
               'page'   => $page,
               'limit'  => $limit,
               'issues' => array(),
            );
            
            $milestone_id = $this->_getParam('milestone_id');
            
            //Load the milestone details
            $m = new Bugify_Milestones();
            $result= $m->fetch($milestone_id);
            
            //Load all issues for the milestone
            $i = new Bugify_Issues();
            $i->setPaginationPage($page);
            $i->setPaginationLimit($limit);
            
            //Set the filter options and fetch the issues
            $filter = $i->filter();
            $filter->setMilestoneIds(array($result->getMilestoneId()));
            $result = $i->fetchAll($filter);
            
            //Process the issues (ie, attach extra info)
            $h = new Bugify_Helpers_Issues();
            $issues = $h->processIssues($result);
            
            /**
             * We don't include the comments or history in this list,
             * so remove the parameters.
             */
            if (is_array($issues) && count($issues) > 0) {
                foreach ($issues as $key => $val) {
                    unset($issues[$key]['attachments']);
                    unset($issues[$key]['comments']);
                    unset($issues[$key]['followers']);
                    unset($issues[$key]['history']);
                }
            }
            
            //Get the pagination info
            $page  = $i->getPaginationPage();
            $limit = $i->getPaginationLimit();
            $total = $i->getTotal();
            
            $data = array(
               'total'  => $total,
               'page'   => $page,
               'limit'  => $limit,
               'issues' => $issues,
            );
            
            echo $this->_formatResult($data);
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
    
    public function historyAction()
    {
        try
        {
            $page   = (int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1;
            $limit  = (int)$this->_getParam('limit') > 0 ? $this->_getParam('limit') : $this->_getDefaultLimit();
            $total  = 0;
            
            $data = array(
               'total'   => $total,
               'page'    => $page,
               'limit'   => $limit,
               'history' => array(),
            );
            
            //Load all open issues
            $i = new Bugify_Issues();
            $i->setPaginationPage($page);
            $i->setPaginationLimit($limit);
            
            $result = $i->fetchHistory('-1 week');
            
            //Process the history (ie, attach extra info)
            $h = new Bugify_Helpers_History();
            $history = $h->attachFullInfo($result, true);
            
            //Get the pagination info
            $page  = $i->getPaginationPage();
            $limit = $i->getPaginationLimit();
            $total = $i->getTotal();
            
            $data = array(
               'total'   => $total,
               'page'    => $page,
               'limit'   => $limit,
               'history' => $history,
            );
            
            echo $this->_formatResult($data);
        }
        catch (Exception $e)
        {
            $this->_handleException($e);
        }
    }
}
