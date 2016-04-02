<?php

class Ui_Cache
{
    private $_enabled   = false;
    private $_lifetime  = 3600;
    private $_cache_dir = '';
    private $_handler   = null;
    
    public function __construct()
    {
        
    }
    
    public function isEnabled()
    {
        return $this->_enabled;
    }
    
    public function getLifetime()
    {
        return $this->_lifetime;
    }
    
    public function getCacheDir()
    {
        return $this->_cache_dir;
    }
    
    public function getTags($key)
    {
        $tags = array();
        
        if ($this->_enabled)
        {
            $meta = $this->_getHandler()->getMetadatas($key);
            $tags = (isset($meta['tags'])) ? $meta['tags'] : $tags;
        }
        
        return $tags;
    }
    
    public function getAllTags()
    {
        $tags = array();
        
        if ($this->_enabled)
        {
            $tags = $this->_getHandler()->getTags();
        }
        
        return $tags;
    }
    
    public function setEnabled($val)
    {
        $this->_enabled = $val;
        
        return $this;
    }
    
    public function setLifetime($val)
    {
        $this->_lifetime = $val;
        
        return $this;
    }
    
    public function setCacheDir($val)
    {
        //Make sure that the folder is writable
        if (is_dir($val) && is_writable($val))
        {
            $this->_cache_dir = $val;
        }
        else
        {
            throw new Exception(sprintf('The path "%s" is not writable.', $val));
        }
        
        return $this;
    }
    
    private function _getHandler()
    {
        if ($this->_handler == null)
        {
            $frontendOptions = array(
               'lifetime'                => $this->getLifetime(),
               'automatic_serialization' => true,
            );
            
            $backendOptions = array(
               'cache_dir' => $this->getCacheDir(),
            );
            
            //Load an instance of standard file cache
            $this->_handler = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
        }
        
        return $this->_handler;
    }
    
    public function load($key)
    {
        $data = false;
        
        if ($this->_enabled)
        {
            //Check if the key is cached
            $data = $this->_getHandler()->load($key);
        }
        
        return $data;
    }
    
    public function save($key, $val, $tags=array(), $lifetime=false)
    {
        if ($this->_enabled)
        {
            //Cache the data
            $this->_getHandler()->save($val, $key, $tags, $lifetime);
        }
    }
    
    public function remove($key)
    {
        if ($this->_enabled)
        {
            //Remove the cache key
            $this->_getHandler()->remove($key);
        }
    }
    
    public function removeWithTags($tags)
    {
        if ($this->_enabled)
        {
            $tags = (is_array($tags)) ? $tags : array($tags);
            
            $this->_getHandler()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, $tags);
        }
    }
    
    public function removeAll()
    {
        if ($this->_enabled)
        {
            $this->_getHandler()->clean(Zend_Cache::CLEANING_MODE_ALL);
        }
    }
}
