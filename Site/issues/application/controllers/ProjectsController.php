<?php

class ProjectsController extends Ui_Controller_Action
{
    public function init()
    {}
    
    public function indexAction()
    {
        try
        {
            $projects = array();
            
            //Load all the projects
            $p = new Bugify_Projects();
            $result = $p->fetchAll();
            
            //Process the projects (attach issue counts etc)
            $h = new Bugify_Helpers_Issues();
            $projects = $h->addIssueCountsForProjects($result);
            
            if ($this->getRequest()->isPost())
            {
                $project = $this->_getParam('project');
                
                //Create a new project
                $new = new Bugify_Project();
                $new->setName($project['name'])
                    ->setState(Bugify_Project::STATE_ACTIVE);
                
                //Save the project
                $p = new Bugify_Projects();
                $p->save($new);
                
                //Load the new project
                $project = $p->fetch($new->getSlug());
                
                //Add the default categories
                $config     = Zend_Registry::get('config');
                $categories = $config->projects->default_categories->toArray();
                
                foreach ($categories as $name)
                {
                    //Create the new category
                    $c = new Bugify_Project_Category();
                    $c->setName($name);
                    
                    //Save the category
                    $project->saveCategory($c);
                }
                
                //Clear cached projects
                $this->cache->removeWithTags('Projects');
                
                //Clear issue counts
                $this->cache->removeWithTags('IssueCount');
                
                Ui_Messages::Add('ok', 'New project has been saved.');
                
                $this->_redirect(sprintf('/projects/%s', $project->getSlug()));
            }
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->projects = $projects;
    }
    
    public function overviewAction()
    {
        try
        {
            $project = array();
            $issues  = array();
            $page    = 1;
            $limit   = 0;
            $total   = 0;
            
            $project_slug = $this->_getParam('project_slug');
            
            //Load the project details
            $p = new Bugify_Projects();
            $result = $p->fetch($project_slug);
            
            if ($this->getRequest()->isPost())
            {
                $project = $this->_getParam('project');
                
                //Update project
                $result->setName($project['name']);
                
                //Save the project
                $p->save($result);
                
                $this->_redirect(sprintf('/projects/%s', $result->getSlug()));
            }
            
            //Load the categories for this project
            $result->getCategories();
            
            $project = $result->toArray();
            
            //Load all open issues for this project
            $i = new Bugify_Issues();
            $i->setPaginationPage((int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1);
            
            //Set the filter options
            $filter = $i->filter();
            $filter->setProjectIds(array($result->getProjectId()));
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
            
            $this->_redirect('/projects');
        }
        
        $this->view->page_title = (isset($project['name'])) ? $project['name'] : '';
        $this->view->project    = $project;
        $this->view->issues     = $issues;
        $this->view->page       = $page;
        $this->view->limit      = $limit;
        $this->view->total      = $total;
    }
    
    public function newCategoryAction()
    {
        try
        {
            $project_slug = $this->_getParam('project_slug');
            
            //Load the project details
            $p = new Bugify_Projects();
            $result = $p->fetch($project_slug);
            
            if ($this->getRequest()->isPost())
            {
                $category = $this->_getParam('category');
                
                if (isset($category['name']))
                {
                    //Create the new category
                    $c = new Bugify_Project_Category();
                    $c->setName($category['name']);
                    
                    //Save the category
                    $result->saveCategory($c);
                    
                    Ui_Messages::Add('ok', 'Category has been added.');
                }
            }
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->_redirect(sprintf('/projects/%s', $project_slug));
    }
    
    public function categoryIssuesAction()
    {
        try
        {
            $project  = array();
            $category = array();
            $issues   = array();
            $page     = 1;
            $limit    = 0;
            $total    = 0;
            
            $project_slug = $this->_getParam('project_slug');
            $category_id  = $this->_getParam('category_id');
            
            //Load the project details
            $p = new Bugify_Projects();
            $result  = $p->fetch($project_slug);
            
            //Load the categories for this project
            $categories = $result->getCategories();
            $found      = false;
            
            foreach ($categories as $c)
            {
                if ($c->getCategoryId() == $category_id)
                {
                    if ($this->getRequest()->isPost())
                    {
                        $updateCategory = $this->_getParam('category');
                        
                        //Update category
                        $c->setName($updateCategory['name']);
                        
                        //Save the category
                        $result->saveCategory($c);
                        
                        Ui_Messages::Add('ok', 'Category has been updated.');
                    }
                    
                    $category = $c->toArray();
                    $found    = true;
                    break;
                }
            }
            
            if ($found === false)
            {
                throw new Ui_Exception('Category not found.', 404);
            }
            
            $project = $result->toArray();
            
            //Load all open issues for this project
            $i = new Bugify_Issues();
            $i->setPaginationPage((int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1);
            
            //Set the filter options
            $filter = $i->filter();
            $filter->setCategoryIds(array($category_id));
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
            
            $this->_redirect('/projects');
        }
        
        $this->view->page_title = (isset($project['name'])) ? $project['name'] : '';
        $this->view->project    = $project;
        $this->view->category   = $category;
        $this->view->issues     = $issues;
        $this->view->page       = $page;
        $this->view->limit      = $limit;
        $this->view->total      = $total;
    }
    
    public function deleteAction()
    {
        try
        {
            $project_slug = $this->_getParam('project_slug');
            
            //Load the project details
            $p = new Bugify_Projects();
            $project = $p->fetch($project_slug);
            
            if ($this->getRequest()->isPost())
            {
                //Load all open issues for this project
                $i = new Bugify_Issues();
                
                //Set the filter options
                $filter = $i->filter();
                $filter->setProjectIds(array($project->getProjectId()));
                $issues = $i->fetchAll($filter);
                
                if (is_array($issues) && count($issues) > 0)
                {
                    foreach ($issues as $issue)
                    {
                        //Update the status
                        $issue->setState(Bugify_Issue::STATE_CLOSED);
                        
                        //Save the issue
                        $i->save($issue, $this->user);
                    }
                }
                
                //Change the project state
                $project->setState(Bugify_Project::STATE_ARCHIVED);
                
                //Save the project
                $p->save($project);
                
                //Clear cached projects
                $this->cache->removeWithTags('Projects');
                
                Ui_Messages::Add('ok', sprintf('Project "%s" has been deleted.', $project->getName()));
                
                $this->_redirect('/projects');
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
        
        $this->_redirect(sprintf('/projects/%s', $project_slug));
    }
    
    public function deleteCategoryAction() {
        try {
            $project_slug = $this->_getParam('project_slug');
            $category_id  = $this->_getParam('category_id');
            
            //Load the project details
            $p = new Bugify_Projects();
            $project = $p->fetch($project_slug);
            
            //Load the categories for this project
            $categories = $project->getCategories();
            $found      = false;
            
            foreach ($categories as $c) {
                if ($c->getCategoryId() == $category_id) {
                    //Mark the category as deleted/archived
                    $c->setState(Bugify_Project_Category::STATE_ARCHIVED);
                    
                    //Load all open issues for this project
                    $i = new Bugify_Issues();
                    
                    //Set the filter options
                    $filter = $i->filter();
                    $filter->setCategoryIds(array($c->getCategoryId()));
                    $issues = $i->fetchAll($filter);
                    
                    if (is_array($issues) && count($issues) > 0) {
                        foreach ($issues as $issue) {
                            //Reset the category for this issue
                            $issue->setCategoryId(0);
                            
                            //Save the issue
                            $i->save($issue, $this->user);
                        }
                    }
                    
                    //Save the category
                    $project->saveCategory($c);
                    
                    $found = true;
                    break;
                }
            }
            
            if ($found === false) {
                throw new Ui_Exception('Category not found.', 404);
            }
            
            Ui_Messages::Add('ok', sprintf('Category "%s" has been deleted.', $c->getName()));
        } catch (Exception $e) {
            if ($e->getCode() == 404) {
                throw $e;
            }
            
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->_redirect(sprintf('/projects/%s', $project->getSlug()));
    }
}
