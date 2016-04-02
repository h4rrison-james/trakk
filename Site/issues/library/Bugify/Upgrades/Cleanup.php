<?php

class Bugify_Upgrades_Cleanup
{
    //List of files/folders to delete after an upgrade
    private $_old_files = array(
       'cli/tools/ssl',
       'cli/tools/get-root-certs',
    );
    
    public function __construct()
    {}
    
    private function _deleteDir($folder)
    {
        $folder = realpath($folder);
        
        if (!file_exists($folder))
        {
            return true;
        }
        
        if (!is_dir($folder))
        {
            return unlink($folder);
        }
        
        foreach (scandir($folder) as $item)
        {
            if ($item == '.' || $item == '..')
            {
                continue;
            }
            
            if (!$this->_deleteDir($folder.'/'.$item))
            {
                return false;
            }
        }
        
        return rmdir($folder);
    }
    
    /**
     * Sometimes we get orphan files that need to be removed
     * to keep everything tidy.  We only ever remove files
     * that are explicitly listed here.
     */
    public function removeOldFiles()
    {
        $config    = Zend_Registry::get('config');
        $base_path = $config->base_path;
        
        if (is_array($this->_old_files) && count($this->_old_files) > 0)
        {
            foreach ($this->_old_files as $path)
            {
                //Build the path
                $full_path = $base_path.'/'.$path;
                
                //Check if the path exists
                if (is_readable($full_path))
                {
                    if (is_dir($full_path))
                    {
                        //Delete the folder
                        $this->_deleteDir($full_path);
                    }
                    elseif (is_file($full_path))
                    {
                        //Delete the file
                        unlink($full_path);
                    }
                }
            }
        }
    }
}
