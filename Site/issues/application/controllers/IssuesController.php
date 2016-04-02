<?php

class IssuesController extends Ui_Controller_Action {
    public function init() {}
    
    public function indexAction() {
        try
        {
            $issues        = array();
            $filter        = array();
            $saved_filter  = array();
            $custom_filter = false;
            $page          = 1;
            $limit         = 0;
            $total         = 0;
            
            //Load all open issues
            $i = new Bugify_Issues();
            $i->setPaginationPage((int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1);
            
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
            
            //Check if we are loading a saved filter
            $params = $this->_getAllParams();
            
            if (isset($params['filter_id']) && (int)$params['filter_id'] > 0)
            {
                $filter_id = (int)$this->_getParam('filter_id');
                
                //Load the filter
                $f = new Bugify_Filters();
                $result = $f->fetch($filter_id);
                
                /**
                 * Make sure this filter is for the logged in user.
                 * todo - how important is this check?
                 */
                if ($result->getUserId() != $this->user->getUserId())
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
                
                $saved_filter = $result->toArray(true);
            }
            
            //Check for filtering options
            $params = $this->_getAllParams();
            
            if (isset($params['filter']))
            {
                $options = $this->_getParam('filter');
                
                if (is_array($options) && count($options) > 0)
                {
                    //This is a custom filter
                    $custom_filter = true;
                    
                    foreach ($options as $key => $val)
                    {
                        if ($key == 'search')
                        {
                            $filter['search'] = (string)$val;
                        }
                        else
                        {
                            $valid_keys = array(
                               'projects',
                               'priorities',
                               'states',
                               'milestones',
                               'users',
                            );
                            
                            if (in_array($key, $valid_keys))
                            {
                                //All these keys expect array values
                                if (is_array($val) && count($val) > 0)
                                {
                                    //Reset the current filter
                                    $filter[$key] = array();
                                    
                                    foreach ($val as $k => $v)
                                    {
                                        if (strlen($v) > 0)
                                        {
                                            $filter[$key][] = (int)$v;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
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
            
            //Get the pagination info
            $page  = $i->getPaginationPage();
            $limit = $i->getPaginationLimit();
            $total = $i->getTotal();
            
            if ($this->getRequest()->isPost())
            {
                $save = $this->_getParam('save_filter');
                
                if (isset($save['name']) && strlen($save['name']) > 0)
                {
                    //Save the filter for re-use later
                    $f->setUserId($this->user->getUserId())
                      ->setName($save['name']);
                    
                    $filters = new Bugify_Filters();
                    $filters->save($f);
                    
                    //Clear filter counts
                    $this->cache->removeWithTags('FilterCount');
                    
                    Ui_Messages::Add('ok', 'Filter has been saved.');
                }
            }
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->filter        = $filter;
        $this->view->saved_filter  = $saved_filter;
        $this->view->custom_filter = $custom_filter;
        $this->view->issues        = $issues;
        $this->view->page          = $page;
        $this->view->limit         = $limit;
        $this->view->total         = $total;
    }
    
    public function forgetFilterAction()
    {
        try
        {
            $filter_id = (int)$this->_getParam('filter_id');
            
            //Load the filter
            $f = new Bugify_Filters();
            $filter = $f->fetch($filter_id);
            
            if ($this->getRequest()->isPost()) {
                //Remove the filter
                $f->remove($filter);
                
                //Clear cached filter counts
                $this->cache->removeWithTags('FilterCount');
                
                Ui_Messages::Add('ok', sprintf('Filter "%s" has been deleted.', $filter->getName()));
            }
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->_redirect('/issues');
    }
    
    public function mineAction()
    {
        try
        {
            $issues = array();
            $page   = 1;
            $limit  = 0;
            $total  = 0;
            
            //Load all open issues for this user
            $i = new Bugify_Issues();
            $i->setPaginationPage((int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1);
            
            //Set the filter options
            $filter = $i->filter();
            $filter->setAssigneeIds(array($this->user->getUserId()));
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
        
        $this->view->issues = $issues;
        $this->view->page   = $page;
        $this->view->limit  = $limit;
        $this->view->total  = $total;
    }
    
    public function followingAction()
    {
        try
        {
            $issues = array();
            $page   = 1;
            $limit  = 0;
            $total  = 0;
            
            //Load all open issues
            $i = new Bugify_Issues();
            $i->setPaginationPage((int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1);
            
            //Fetch the follows by this user
            $follows   = $i->fetchFollowsByUser($this->user);
            $issue_ids = array();
            
            if (count($follows) > 0) {
                foreach ($follows as $follower) {
                    $issue_ids[] = $follower->getIssueId();
                }
                
                //Set the filter options
                $filter = $i->filter();
                $filter->setStates($i->getAllStates())
                       ->setIssueIds($issue_ids);
                $result = $i->fetchAll($filter);
                
                //Process the issues (ie, attach extra info)
                $h = new Bugify_Helpers_Issues();
                $issues = $h->processIssues($result);
            }
            
            //Get the pagination info
            $page  = $i->getPaginationPage();
            $limit = $i->getPaginationLimit();
            $total = $i->getTotal();
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->issues = $issues;
        $this->view->page   = $page;
        $this->view->limit  = $limit;
        $this->view->total  = $total;
    }
    
    public function filtersAction()
    {
        try
        {
            $filters = array();
            
            //Load the saved filters for this user
            $f = new Bugify_Filters();
            $result = $f->fetchAllForUser($this->user);
            
            foreach ($result as $filter)
            {
                $filters[] = $filter->toArray();
            }
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->filters = $filters;
    }
    
    public function overviewAction()
    {
        try
        {
            $issue_id = $this->_getParam('issue_id');
            
            //Load the issue details
            $i = new Bugify_Issues();
            $issue = $i->fetch($issue_id);
            
            if ($this->getRequest()->isPost())
            {
                $params = $this->_getAllParams();
                
                if (isset($params['attach']))
                {
                    if (!isset($_FILES['attachment']))
                    {
                        Ui_Messages::Add('error', 'Could not save the attachment.  Try a smaller filesize.');
                        
                        $this->_redirect(sprintf('/issues/%s', $issue->getIssueId()));
                    }
                    
                    /**
                     * The user is uploading a file, put the array
                     * into something we can easily loop through
                     */
                    $files = array();
                    
                    foreach ($_FILES['attachment'] as $key => $val)
                    {
                        foreach ($val as $k => $v)
                        {
                            $files[$k][$key] = $v;
                        }
                    }
                    
                    $config = Zend_Registry::get('config');
                    $errors = array();
                    
                    //Now process the uploaded files
                    foreach ($files as $key => $val)
                    {
                        try
                        {
                            if ($val['error'] == 0)
                            {
                                //Work out the folder to store the file in
                                $folder    = $issue->getIssueId();
                                $filename  = md5($issue_id.$val['name'].time());
                                $full_path = $config->base_path.$config->storage->attachments.'/'.$folder;
                                $file_path = $full_path.'/'.$filename;
                                
                                if (!is_dir($full_path))
                                {
                                    mkdir($full_path, 0755, true);
                                }
                                
                                //Move the uploaded file
                                if (move_uploaded_file($val['tmp_name'], $file_path))
                                {
                                    //Create a new attachment object
                                    $a = new Bugify_Issue_Attachment();
                                    $a->setName($val['name'])
                                      ->setUserId($this->user->getUserId())
                                      ->setFilename($filename)
                                      ->setFilesize($val['size'])
                                      ->setState(Bugify_Issue_Attachment::STATE_ACTIVE);
                                    
                                    //Save the attachment details
                                    $issue->saveAttachment($a);
                                }
                                else
                                {
                                    throw new Ui_Exception(sprintf('Unable to store the uploaded file "%s".', $val['name']));
                                }
                            }
                            else
                            {
                                switch ((int)$val['error'])
                                {
                                    case UPLOAD_ERR_INI_SIZE:
                                        $message = 'The file is too large.';
                                        break;
                                    case UPLOAD_ERR_FORM_SIZE:
                                        $message = 'The file is too large.';
                                        break;
                                    case UPLOAD_ERR_PARTIAL:
                                        $message = 'The file was only partially uploaded.';
                                        break;
                                    case UPLOAD_ERR_NO_FILE:
                                        $message = 'No file was uploaded.';
                                        break;
                                    case UPLOAD_ERR_NO_TMP_DIR:
                                        $message = 'The system temporary folder is missing.';
                                        break;
                                    case UPLOAD_ERR_CANT_WRITE:
                                        $message = 'Failed to write to disk.';
                                        break;
                                    case UPLOAD_ERR_EXTENSION:
                                        $message = 'An extension prevented the upload.';
                                        break;
                                    default:
                                        $message = 'Unknown error.';
                                        break;
                                }
                                
                                if (strlen($val['name']) > 0)
                                {
                                    throw new Ui_Exception(sprintf('The file "%s" could not be uploaded. %s', $val['name'], $message));
                                }
                                else
                                {
                                    throw new Ui_Exception($message);
                                }
                            }
                        }
                        catch (Exception $e)
                        {
                            $errors[] = $e->getMessage();
                        }
                    }
                    
                    if (count($errors) > 0)
                    {
                        Ui_Messages::Add('error', implode(' ', $errors));
                    }
                    
                    //Update search index
                    $this->search->updateIssueDocument($issue);
                    
                    //Reload the issue
                    $this->_redirect(sprintf('/issues/%s', $issue->getIssueId()));
                }
                elseif (isset($params['comment']))
                {
                    //Adding a comment
                    $comment = $this->_getParam('comment');
                    
                    if (strlen($comment) > 0)
                    {
                        //Create a new comment
                        $c = new Bugify_Issue_Comment();
                        $c->setUserId($this->user->getUserId())
                          ->setComment($comment)
                          ->setState(Bugify_Issue_Comment::STATE_ACTIVE);
                        
                        //Save the comment
                        $issue->saveComment($c);
                        
                        //Update search index
                        $this->search->updateIssueDocument($issue);
                        
                        //Reload the issue
                        $this->_redirect(sprintf('/issues/%s', $issue->getIssueId()));
                    }
                }
                elseif (isset($params['editcomment']))
                {
                    //Edit a comment
                    $edit = $this->_getParam('editcomment');
                    
                    if (isset($edit['comment']) && isset($edit['id']))
                    {
                        //Find the comment
                        $comments = $issue->getComments();
                        $found    = false;
                        
                        foreach ($comments as $comment)
                        {
                            if ($comment->getCommentId() == $edit['id'])
                            {
                                //This is the issue
                                $found = true;
                                break;
                            }
                        }
                        
                        if ($found === false)
                        {
                            throw new Ui_Exception('The specified comment cannot be found.');
                        }
                        
                        //Edit the comment
                        $comment->setComment($edit['comment']);
                        
                        //Save the comment
                        $issue->saveComment($comment);
                        
                        //Update search index
                        $this->search->updateIssueDocument($issue);
                        
                        //Reload the issue
                        $this->_redirect(sprintf('/issues/%s', $issue->getIssueId()));
                    }
                }
                elseif (isset($params['issue']))
                {
                    //Editing the issue
                    $edit = $this->_getParam('issue');
                    
                    $category_id = (isset($edit['category'])) ? $edit['category'] : 0;
                    
                    //Update the issue details
                    $issue->setProjectId($edit['project'])
                          ->setCategoryId($category_id)
                          ->setMilestoneId($edit['milestone'])
                          ->setAssigneeId($edit['assignee'])
                          ->setSubject($edit['subject'])
                          ->setDescription($edit['description'])
                          ->setPriority($edit['priority'])
                          ->setPercentage($edit['percentage']);
                    
                    //Save the issue
                    $i->save($issue, $this->user);
                    
                    //Clear issue counts
                    $this->cache->removeWithTags('IssueCount');
                    
                    Ui_Messages::Add('ok', 'Issue has been updated.');
                    
                    //Update search index
                    $this->search->updateIssueDocument($issue);
                    
                    $this->_redirect(sprintf('/issues/%s', $issue->getIssueId()));
                }
                elseif (isset($params['status']))
                {
                    //Changing issue state (and maybe assignee)
                    $status = $this->_getParam('status');
                    
                    //Update the issue state
                    $issue->setPercentage($status['percentage'])
                          ->setState($status['state'])
                          ->setAssigneeId($status['assignee']);
                    
                    //Save the issue
                    $i->save($issue, $this->user);
                    
                    //Clear issue counts
                    $this->cache->removeWithTags('IssueCount');
                    
                    Ui_Messages::Add('ok', 'Issue has been updated.');
                    
                    //Clear follow counts
                    $this->cache->removeWithTags('FollowCount');
                    
                    $this->_redirect(sprintf('/issues/%s', $issue->getIssueId()));
                }
            }
            
            //Load attachment info
            $issue->getAttachments();
            
            //Load comments
            $issue->getComments();
            
            //Load the followers
            $issue->getFollowers();
            
            //Process the issue (ie, attach extra info)
            $h = new Bugify_Helpers_Issues();
            $issue = $h->processIssues(array($issue));
            $issue = current($issue);
            
            //Load projects (for editing the issue)
            $p = new Bugify_Projects();
            $result     = $p->fetchAll();
            $projects   = array();
            $categories = array();
            
            foreach ($result as $project)
            {
                $projects[] = $project->toArray();
                
                if ($project->getProjectId() == $issue['project_id'])
                {
                    /**
                     * Load the categories for this project because
                     * it is the project this issue is currently assigned to.
                     * This allows the edit box to pre-fill the categories
                     * for this project.
                     */
                    $project_categories = $project->getCategories();
                    
                    foreach ($project_categories as $category)
                    {
                        $categories[] = $category->toArray();
                    }
                }
            }
            
            //Load milestones
            $m = new Bugify_Milestones();
            $result     = $m->fetchAll();
            $milestones = array();
            
            foreach ($result as $milestone)
            {
                $milestones[] = $milestone->toArray();
            }
            
            //Load priorities (for editing the issue)
            $i = new Bugify_Issues();
            $priorities = $i->getPriorities();
            
            //Load users (for re-assigning)
            $c = new Bugify_Users();
            $result = $c->fetchAll();
            $users  = array();
            
            foreach ($result as $user)
            {
                $users[] = $user->toArray();
            }
            
            
            /**
             * We keep track of the most recent 3 issues
             * visited by each user so they can quickly access
             * them from the main menu.
             */
            $s = new Zend_Session_Namespace('RecentIssues');
            $s->issues = (isset($s->issues)) ? $s->issues : array();
            
            //Add this issue to the beginning of the list
            array_unshift($s->issues, $issue);
            
            //Remove any duplicate issues
            $issue_ids = array();
            
            foreach ($s->issues as $key => $val)
            {
                if (!in_array($val['id'], $issue_ids))
                {
                    $issue_ids[] = $val['id'];
                }
                else
                {
                    unset($s->issues[$key]);
                }
            }
            
            //Drop off anything after 3 issues
            $s->issues = array_slice($s->issues, 0, 3);
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
            
            $this->_redirect('/issues/mine');
        }
        
        $this->view->issue      = $issue;
        $this->view->page_title = (isset($issue['subject'])) ? $issue['subject'] : '';
        $this->view->projects   = $projects;
        $this->view->categories = $categories;
        $this->view->milestones = $milestones;
        $this->view->users      = $users;
        $this->view->priorities = $priorities;
    }
    
    public function newAction()
    {
        try
        {
            //Check if we are adding an issue for a specific project
            $params = $this->_getAllParams();
            
            if (isset($params['issue'])) {
                //Use the selected project id from the posted form
                $project_id = (isset($params['issue']['project'])) ? (int)$params['issue']['project'] : 0;
            } else {
                //Use the specified project id in the url params
                $project_id = (isset($params['project'])) ? (int)$params['project'] : 0;
            }
            
            //Check if we are adding an issue for a specific category
            $category_id = (isset($params['category'])) ? (int)$params['category'] : 0;
            
            //Check if we are adding an issue for a specific milestone
            $milestone_id = (isset($params['milestone'])) ? (int)$params['milestone'] : 0;
            
            /**
             * Check if we are adding an issue for a specific user.
             * We work out the assignee_id based off the username further down.
             */
            $username    = (isset($params['user'])) ? $params['user'] : '';
            $assignee_id = 0;
            
            //Load projects
            $p = new Bugify_Projects();
            $result     = $p->fetchAll();
            $projects   = array();
            $categories = array();
            
            foreach ($result as $project)
            {
                $projects[] = $project->toArray();
                
                if ($project->getProjectId() == $project_id)
                {
                    //This is the pre-selected project - fetch the categories
                    $c = $project->getCategories();
                    
                    if (is_array($c) && count($c) > 0)
                    {
                        foreach ($c as $category)
                        {
                            $categories[] = $category->toArray();
                        }
                    }
                }
            }
            
            //Load priorities
            $i = new Bugify_Issues();
            $priorities = $i->getPriorities();
            
            //Load users (for assigning)
            $c = new Bugify_Users();
            $result = $c->fetchAll();
            $users  = array();
            
            foreach ($result as $user)
            {
                $users[] = $user->toArray();
                
                if (strlen($username) > 0 && $user->getUsername() == $username) {
                    $assignee_id = $user->getUserId();
                }
            }
            
            //Load milestones
            $m = new Bugify_Milestones();
            $result     = $m->fetchAll();
            $milestones = array();
            
            foreach ($result as $milestone)
            {
                $milestones[] = $milestone->toArray();
            }
            
            //Set defaults
            $issue = array(
               'subject'      => '',
               'description'  => '',
               'assignee'     => $assignee_id,
               'project'      => $project_id,
               'category'     => $category_id,
               'milestone'    => $milestone_id,
               'priority'     => Bugify_Issue::PRIORITY_NORMAL,
            );
            
            if ($this->getRequest()->isPost())
            {
                $issue = $this->_getParam('issue');
                
                $category_id = (isset($issue['category'])) ? $issue['category'] : 0;
                
                //Create a new issue
                $new = new Bugify_Issue();
                $new->setProjectId($issue['project'])
                    ->setCategoryId($category_id)
                    ->setMilestoneId($issue['milestone'])
                    ->setCreatorId($this->user->getUserId())
                    ->setAssigneeId($issue['assignee'])
                    ->setSubject($issue['subject'])
                    ->setDescription($issue['description'])
                    ->setPriority($issue['priority'])
                    ->setState(Bugify_Issue::STATE_OPEN);
                
                //Save the issue
                $id = $i->save($new);
                
                //Reload the issue
                $issue = $i->fetch($id);
                
                //Clear issue counts
                $this->cache->removeWithTags('IssueCount');
                
                Ui_Messages::Add('ok', sprintf('Issue #%s has been saved.', $id, $id));
                
                //Add to search index
                $this->search->addIssueDocument($issue);
                
                $this->_redirect('/');
            }
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->issue      = $issue;
        $this->view->projects   = $projects;
        $this->view->categories = $categories;
        $this->view->priorities = $priorities;
        $this->view->users      = $users;
        $this->view->milestones = $milestones;
    }
    
    public function jsGetCategoriesAction()
    {
        try
        {
            $project_id = (int)$this->_getParam('project_id');
            
            //Load the project details
            $p = new Bugify_Projects();
            $result  = $p->fetch($project_id);
            
            //Load the categories for this project
            $categories = $result->getCategories();
            
            $result = array();
            
            if (is_array($categories) && count($categories) > 0)
            {
                foreach ($categories as $category)
                {
                    $result[] = $category->toArray();
                }
            }
            
            $data = array(
               'status'     => true,
               'categories' => $result,
            );
        }
        catch (Exception $e)
        {
            $data = array(
               'error'  => $e->getMessage(),
               'status' => false,
            );
        }
        
        $this->_helper->json($data);
    }
    
    public function jsFollowIssueAction()
    {
        try
        {
            $issue_id = (int)$this->_getParam('issue_id');
            
            //Load the issue details
            $i = new Bugify_Issues();
            $issue = $i->fetch($issue_id);
            
            //Add the current user as a follower
            $f = new Bugify_Issue_Follower();
            $f->setUserId($this->user->getUserId());
            
            //Save the follower
            $issue->saveFollower($f);
            
            //Clear follow counts
            $this->cache->removeWithTags('FollowCount');
            
            $data = array(
               'status' => true,
            );
        }
        catch (Exception $e)
        {
            $data = array(
               'error'  => $e->getMessage(),
               'status' => false,
            );
        }
        
        $this->_helper->json($data);
    }
    
    public function jsUnfollowIssueAction()
    {
        try
        {
            $issue_id = (int)$this->_getParam('issue_id');
            
            //Load the issue details
            $i = new Bugify_Issues();
            $issue = $i->fetch($issue_id);
            
            //Load the followers
            $followers = $issue->getFollowers();
            
            if (is_array($followers) && count($followers) > 0)
            {
                foreach ($followers as $follower)
                {
                    if ($follower->getUserId() == $this->user->getUserId())
                    {
                        //Remove this follower
                        $issue->removeFollower($follower);
                    }
                }
            }
            
            //Clear follow counts
            $this->cache->removeWithTags('FollowCount');
            
            $data = array(
               'status' => true,
            );
        }
        catch (Exception $e)
        {
            $data = array(
               'error'  => $e->getMessage(),
               'status' => false,
            );
        }
        
        $this->_helper->json($data);
    }
    
    public function jsGetHistoryAction()
    {
        try
        {
            $issue_id = (int)$this->_getParam('issue_id');
            
            //Load the issue details
            $i = new Bugify_Issues();
            $issue = $i->fetch($issue_id);
            
            //Load the history
            $history = $issue->getHistory();
            
            //Process the history (ie, attach extra info)
            $h = new Bugify_Helpers_History();
            $history = $h->attachFullInfo($history);
            
            //Convert history to html table
            $html = $this->view->ListHistory($history);
            
            $data = array(
               'status'  => true,
               'history' => $html,
            );
        }
        catch (Exception $e)
        {
            $data = array(
               'error'  => $e->getMessage(),
               'status' => false,
            );
        }
        
        $this->_helper->json($data);
    }
    
    public function jsGetRelatedIssuesAction() {
        try {
            $issueId = (int)$this->_getParam('issueId');
            $html    = '';
            
            if ($issueId > 0) {
                //Load the issue details
                $i = new Bugify_Issues();
                $issue = $i->fetch($issueId);
                
                //Load the related issue ids
                $relatedIssueIds = $issue->getRelatedIssueIds();
                
                if (is_array($relatedIssueIds) && count($relatedIssueIds) > 0) {
                    //Load the full details for these issues
                    $filter = $i->filter();
                    $filter->setIssueIds($relatedIssueIds)
                           ->setStates($i->getAllStates());
                    
                    $result = $i->fetchAll($filter);
                    $issues = array();
                    
                    if (is_array($result) && count($result) > 0) {
                        foreach ($result as $related) {
                            $issues[] = $related->toArray();
                        }
                        
                        //Convert related issues to html table
                        $html = $this->view->ListRelatedIssues($issue->toArray(), $issues);
                    }
                }
            }
            
            $data = array(
               'status'  => true,
               'related' => $html,
            );
        } catch (Exception $e) {
            $data = array(
               'error'  => $e->getMessage(),
               'status' => false,
            );
        }
        
        $this->_helper->json($data);
    }
    
    public function jsRemoveRelatedIssueAction() {
        try {
            $issueId        = (int)$this->_getParam('issueId');
            $relatedIssueId = (int)$this->_getParam('relatedIssueId');
            
            //Load the issue details
            $i = new Bugify_Issues();
            $issue = $i->fetch($issueId);
            
            //Remove the related issue id
            $issue->removeRelatedIssueId($relatedIssueId);
            
            //Save the issue
            $i->save($issue);
            
            $data = array(
               'status'  => true,
            );
        } catch (Exception $e) {
            $data = array(
               'error'  => $e->getMessage(),
               'status' => false,
            );
        }
        
        $this->_helper->json($data);
    }
}
