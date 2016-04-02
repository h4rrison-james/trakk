<?php

class SettingsController extends Ui_Controller_Action
{
    public function init()
    {}
    
    public function cacheAction()
    {
        try
        {
            $writable = false;
            $config   = Zend_Registry::get('config');
            
            //Work out the path to the custom config file
            $config_path = $config->base_path.'/library/config.php';
            
            //Load the custom config file
            if (file_exists($config_path))
            {
                //Make sure the config file is writable
                if (is_writable($config_path))
                {
                    $writable = true;
                }
                else
                {
                    Ui_Messages::Add('error', 'Settings cannot be saved because the config file is not writable.');
                }
            }
            else
            {
                Ui_Messages::Add('error', 'Settings cannot be saved because there is no config file.');
            }
            
            //Check if cache is enabled
            $enabled = ($config->cache->enabled == true) ? true : false;
            
            //Get all the cache tags
            $allTags = $this->cache->getAllTags();
            $tags    = array();
            
            /**
             * Get the friendly names for the tags
             */
            $tagNames = array(
                'Gravatar'    => 'Gravatar Images',
                'jpeg'        => 'Images (JPG)',
                'png'         => 'Images (PNG)',
                'Milestones'  => 'Milestones',
                'IssueCount'  => 'Issue Count',
                'FilterCount' => 'Filter Count',
                'FollowCount' => 'Follow Count',
                'Projects'    => 'Projects',
                'Zend_Locale' => 'Date and Locale data',
            );
            
            if (is_array($allTags) && count($allTags) > 0) {
                foreach ($allTags as $tag) {
                    $tagName = (array_key_exists($tag, $tagNames)) ? $tagNames[$tag] : $tag;
                    
                    //We hash the tag name to disguise it in the ui
                    $tags[md5($tag)] = $tagName;
                }
            }
            
            //Check for actions
            $params = $this->_getAllParams();
            
            if (isset($params['clear-tag']))
            {
                //We are cleaning all cached records tagged with this tag
                if (array_key_exists($params['clear-tag'], $tags))
                {
                    $clearTag     = '';
                    $clearTagName = '';
                    
                    //Get the actual tag name
                    foreach ($allTags as $tag) {
                        if (md5($tag) == $params['clear-tag']) {
                            $clearTag     = $tag;
                            $clearTagName = $tags[md5($tag)];
                            break;
                        }
                    }
                    
                    $this->cache->removeWithTags($clearTag);
                    
                    Ui_Messages::Add('ok', sprintf('Cached data tagged with "%s" have been cleared.  Please remember that the cache may have been re-populated again already.', $clearTagName));
                    
                    $this->_redirect('/settings/cache');
                }
            }
            elseif (isset($params['clear-all']))
            {
                $this->cache->removeAll();
                
                Ui_Messages::Add('ok', 'All cached data has been cleared.  Please remember that the cache may have been re-populated again already.');
                
                $this->_redirect('/settings/cache');
            }
            
            if ($this->getRequest()->isPost())
            {
                //Get the cache settings
                $settings = $this->_getParam('settings');
                $state    = (isset($settings['state']) && $settings['state'] == 1) ? true : false;
                
                if ($writable === true)
                {
                    if ($state === false)
                    {
                        //Disabling cache, so clear all current records
                        $this->cache->removeAll();
                    }
                    
                    //Load the custom config file
                    $custom_config = new Zend_Config(require $config_path, true);
                    
                    //Get the current cache config
                    $cache = (isset($custom_config->cache)) ? $custom_config->cache->toArray() : array();
                    $cache['enabled'] = $state;
                    
                    //Set the new state
                    $custom_config->cache = $cache;
                    
                    //Save the config file
                    $w = new Zend_Config_Writer_Array();
                    $w->setConfig($custom_config);
                    
                    //Save the config file
                    if (file_put_contents($config_path, $w->render()) !== false)
                    {
                        Ui_Messages::Add('ok', 'Cache settings have been saved.');
                    }
                    else
                    {
                        throw new Ui_Exception('Could not save settings.  Please check the permissions on the config file.');
                    }
                    
                    $this->_redirect('/settings/cache');
                }
                else
                {
                    throw new Ui_Exception('Could not save changes because the config file is not writable.');
                }
            }
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->config_writable = $writable;
        $this->view->cache_enabled   = $enabled;
        $this->view->tags            = $tags;
    }
}
