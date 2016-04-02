<?php

class Bugify_Upgrades
{
    private $_upgrade_check_url = '';
    private $_last_checked      = 0;
    private $_check_freshness   = 43200; //Check every 12 hours
    
    private $_available = array(
       'version'   => '',
       'released'  => '',
       'link'      => '',
       'signature' => '',
       'changelog' => '',
       'url'       => '',
    );
    
    public function __construct($url, $channel)
    {
        //Save the upgrade check URL
        $this->_upgrade_check_url = $url.'?channel='.$channel;
        
        //Load the saved upgrade info from the db
        $this->_loadUpgradeInfo();
    }
    
    private function _loadUpgradeInfo($rerunning=false)
    {
        //Load the upgrade info from the db
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('upgrades')
          ->limit(1);
        
        $result = $db->fetchAll($s);
        
        if (is_array($result) && count($result) > 0)
        {
            $result = current($result);
            
            //Make sure the check URL matches the current URL
            if ($result['upgrade_check_url'] == $this->_upgrade_check_url)
            {
                $this->_available['version']   = $result['version'];
                $this->_available['released']  = Bugify_Date::getLocalTime($result['released']);
                $this->_available['link']      = $result['link'];
                $this->_available['signature'] = $result['signature'];
                $this->_available['changelog'] = $result['changelog'];
                $this->_available['url']       = $result['upgrade_check_url'];
                $this->_last_checked           = $result['last_checked'];
            }
            else
            {
                //Force an upgrade check now because the check URL has changed
                $this->_checkForUpgrade();
                
                $this->_save();
            }
        }
        elseif ($rerunning === false)
        {
            //Upgrade row doesnt exist, add it now
            $data = array(
               'version'           => $this->_available['version'],
               'released'          => Bugify_Date::getLocalTime($this->_available['released']),
               'link'              => $this->_available['link'],
               'signature'         => $this->_available['signature'],
               'changelog'         => $this->_available['changelog'],
               'upgrade_check_url' => $this->_available['url'],
               'last_checked'      => $this->_last_checked,
            );
            
            $db->insert('upgrades', $data);
            
            //Now re-run this method because we have the data in the db now
            $this->_loadUpgradeInfo(true);
        }
        else
        {
            throw new Bugify_Exception('Could not load upgrade information.');
        }
    }
    
    private function _save()
    {
        $db = Bugify_Db::get();
        
        $data = array(
           'version'           => $this->_available['version'],
           'released'          => Bugify_Date::getTimestamp($this->_available['released']),
           'link'              => $this->_available['link'],
           'signature'         => $this->_available['signature'],
           'changelog'         => $this->_available['changelog'],
           'upgrade_check_url' => $this->_available['url'],
           'last_checked'      => $this->_last_checked,
        );
        
        $db->update('upgrades', $data);
    }
    
    private function _getVersion()
    {
        return Bugify_Version::VERSION;
    }
    
    private function _getLicenceKey()
    {
        $config = Zend_Registry::get('config');
        
        if (isset($config->licence) && strlen($config->licence) > 0)
        {
            $licence = $config->licence;
        }
        else
        {
            throw new Bugify_Exception('Cannot check for updates because the licence key is missing.');
        }
        
        return $licence;
    }
    
    private function _checkForUpgrade()
    {
        //Generate the request headers for authenticating against the upgrade server
        $headers = array(
           'X-Bugify-Version' => $this->_getVersion(),
           'X-Bugify-Licence' => $this->_getLicenceKey(),
           'X-Request-Host'   => Bugify_Host::getHostname(),
        );
        
        $c = new Bugify_Curl();
        $c->setUrl($this->_upgrade_check_url)
          ->setHeaders($headers)
          ->request();
        
        if ($c->isSuccess())
        {
            $result = $c->getBody();
            
            if (strlen($result) > 0)
            {
                $result = json_decode($result, true);
                
                //Save the upgrade data
                $this->_available['version']   = $result['version'];
                $this->_available['released']  = Bugify_Date::getLocalTime($result['created']);
                $this->_available['link']      = $result['link'];
                $this->_available['signature'] = $result['signature'];
                $this->_available['changelog'] = (isset($result['changelog'])) ? $result['changelog'] : '';
                $this->_available['url']       = $this->_upgrade_check_url;
                
                $this->_last_checked = time();
                
                //Save the upgrade info to db
                $this->_save();
                
                return true;
            }
            else
            {
                throw new Bugify_Exception('Empty response from upgrade server.');
            }
        }
        else
        {
            //Check for an error message
            $result  = $c->getBody();
            $message = '';
            
            if (strlen($result) > 0)
            {
                $result  = json_decode($result, true);
                $message = (isset($result['error'])) ? $result['error'] : '';
            }
            
            if (strlen($message) > 0)
            {
                throw new Bugify_Exception($message);
            }
            else
            {
                throw new Bugify_Exception($c->getErrorMessage());
            }
        }
        
        return false;
    }
    
    private function _getMaintenanceModeFilename()
    {
        $config   = Zend_Registry::get('config');
        $filename = $config->public_path.'/.maintenance';
        
        return $filename;
    }
    
    private function _beginMaintenanceMode()
    {
        /**
         * Wait a couple of seconds to allow things like the throbber
         * image to load before maintenance mode is enabled.
         */
        sleep(2);
        
        $filename = $this->_getMaintenanceModeFilename();
        $folder   = dirname($filename);
        
        //Make sure the folder is writable
        if (!is_writable($folder))
        {
            throw new Bugify_Exception('Unable to put website into maintenance mode.  Please ensure the public folder is writable.');
        }
        
        //Create a .maintenance file
        touch($filename);
    }
    
    private function _endMaintenanceMode()
    {
        $filename = $this->_getMaintenanceModeFilename();
        
        if (file_exists($filename))
        {
            unlink($filename);
        }
    }
    
    private function _parseReceipt($receipt)
    {
        /**
         * The receipt file is expected in the following format:
         * hash:XXX|path:XXX|exec:1
         * The path is relative to the root Bugify folder.
         */
        if (strpos($receipt, "\n") !== false)
        {
            $lines = explode("\n", $receipt);
            $paths = array();
            
            unset($receipt);
            
            foreach ($lines as $line)
            {
                if (strlen(trim($line)) > 0)
                {
                    $parts = explode('|', $line);
                    $hash  = null;
                    $path  = null;
                    $exec  = null;
                    
                    foreach ($parts as $part)
                    {
                        $type = substr($part, 0, strpos($part, ':'));
                        $data = substr($part, strpos($part, ':')+1);
                        
                        switch ($type)
                        {
                            case 'hash':
                                $hash = $data;
                                break;
                            case 'path':
                                $path = $data;
                                break;
                            case 'exec':
                                $exec = $data;
                                break;
                        }
                    }
                    
                    if ($hash !== null && $path !== null && $exec !== null)
                    {
                        $paths[sha1($hash.$path)] = array(
                           'hash' => $hash,
                           'path' => $path,
                           'exec' => $exec,
                        );
                    }
                    else
                    {
                        throw new Bugify_Exception('The receipt line could not be parsed.');
                    }
                }
            }
            
            return $paths;
        }
        else
        {
            throw new Bugify_Exception('The receipt file could not be parsed.');
        }
    }
    
    private function _downloadAndDecompressUpdate()
    {
        //Generate a temp filename for the download
        $folder   = sys_get_temp_dir().'/'.uniqid('upgr-');
        $filename = $folder.'/upgrade.zip';
        
        //Create the folder and set usable permissions
        if (!is_dir($folder))
        {
            mkdir($folder);
        }
        
        try
        {
            //Download the update file
            $c = new Bugify_Curl();
            $c->setUrl($this->_available['link'])
              ->saveToLocalPath($filename)
              ->request();
            
            if ($c->isSuccess())
            {
                //Make sure the signature is valid
                $hash = sha1_file($filename);
                
                if ($hash != $this->_available['signature'])
                {
                    throw new Bugify_Exception('The signatures do not match.  The upgrade will not continue.');
                }
                
                //Generate a name for the decompressed data
                $decompressed = $folder.'/'.uniqid('decom-');
                
                //Decompress the file
                $this->_decompressFile($filename, $decompressed);
                
                //Get the receipt file
                $receipt_file = $decompressed.'/bugify/receipt.txt';
                
                if (file_exists($receipt_file))
                {
                    $receipt = file_get_contents($receipt_file);
                    
                    //Process the receipt
                    $path_receipts = $this->_parseReceipt($receipt);
                }
                else
                {
                    throw new Bugify_Exception('The receipt file cannot be found.');
                }
                
                //Copy the extracted files overtop of the existing files
                $config    = Zend_Registry::get('config');
                $base_path = $config->base_path;
                
                $this->_copyFiles($decompressed, $base_path, $path_receipts);
                
                //Remove the upgrade files
                if (is_dir($folder))
                {
                    $this->_deleteDir($folder);
                }
            }
            elseif ($c->getResponseCode() == 404)
            {
                throw new Bugify_Exception('The upgrade file could not be found.');
            }
            else
            {
                throw new Bugify_Exception($c->getErrorMessage());
            }
        }
        catch (Exception $e)
        {
            //Remove the upgrade files
            if (is_dir($folder))
            {
                $this->_deleteDir($folder);
            }
            
            throw $e;
        }
    }
    
    private function _deleteDir($folder, $check_tmp=true)
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
        
        if ($check_tmp === true)
        {
            //Make sure the folder is beneath /tmp
            $tmp = sys_get_temp_dir();
            
            if (substr($folder, 0, strlen($tmp)) != $tmp)
            {
                throw new Bugify_Exception('Cannot delete a folder that is not in temp.');
            }
        }
        
        foreach (scandir($folder) as $item)
        {
            if ($item == '.' || $item == '..')
            {
                continue;
            }
            
            if (!$this->_deleteDir($folder.'/'.$item, false))
            {
                return false;
            }
        }
        
        return rmdir($folder);
    }
    
    private function _decompressFile($zip_filename, $decompress_to)
    {
        try
        {
            if (version_compare(PHP_VERSION, '5.3.0', '<'))
            {
                //Older than PHP 5.3 so attempt alternate methods
                if (is_readable('/usr/bin/unzip'))
                {
                    $output = array();
                    $result = null;
                    
                    exec(sprintf('/usr/bin/unzip %s -d %s', $zip_filename, $decompress_to), $output, $result);
                    
                    if ($result != 0)
                    {
                        throw new Bugify_Exception('Could not extract the upgrade.  Please install "unzip" or upgrade to PHP 5.3+');
                    }
                }
                else
                {
                    throw new Bugify_Exception('Could not find any tools to extract the upgrade.  Please install "unzip" or upgrade to PHP 5.3+');
                }
            }
            else
            {
                //PHP 5.3+ so we can use the ZipArchive code
                $zip = new ZipArchive();
                
                //Open the zip file
                $result = $zip->open($zip_filename);
                
                if ($result === true)
                {
                    //Extract the file
                    if ($zip->extractTo($decompress_to))
                    {
                        $zip->close();
                        
                        return true;
                    }
                    else
                    {
                        throw new Bugify_Exception('Could not extract the upgrade file.');
                    }
                }
                else
                {
                    switch ($result)
                    {
                        case ZIPARCHIVE::ER_EXISTS:
                            throw new Bugify_Exception('File already exists.');
                            break;
                        case ZIPARCHIVE::ER_INCONS:
                            throw new Bugify_Exception('Zip archive is inconsistent.');
                            break;
                        case ZIPARCHIVE::ER_INVAL:
                            throw new Bugify_Exception('Invalid argument.');
                            break;
                        case ZIPARCHIVE::ER_MEMORY:
                            throw new Bugify_Exception('Memory allocation failure.');
                            break;
                        case ZIPARCHIVE::ER_NOENT:
                            throw new Bugify_Exception('No such file.');
                            break;
                        case ZIPARCHIVE::ER_NOZIP:
                            throw new Bugify_Exception('Not a zip archive.');
                            break;
                        case ZIPARCHIVE::ER_OPEN:
                            throw new Bugify_Exception('Cannot open file.');
                            break;
                        case ZIPARCHIVE::ER_READ:
                            throw new Bugify_Exception('Read error.');
                            break;
                        case ZIPARCHIVE::ER_SEEK:
                            throw new Bugify_Exception('Seek error.');
                            break;
                        default:
                            throw new Bugify_Exception('Could not load the upgrade file.');
                    }
                }
            }
        }
        catch (Exception $e)
        {
            throw new Bugify_Exception('Problem extracting the upgrade file: '.$e->getMessage());
        }
    }
    
    private function _copyFiles($from, $to, $path_receipts=array())
    {
        /**
         * The $from folder should have a folder named "bugify" within it.
         * Check that it exists.
         */
        $expected_folder = 'bugify';
        
        //List the items to check for the expected folder
        $items = scandir($from);
        
        if (!in_array($expected_folder, $items))
        {
            throw new Bugify_Exception('The upgrade was not in the expected format.');
        }
        
        //Make sure the destination folder is writable
        if (!is_writable($to))
        {
            throw new Bugify_Exception('The destination folder is not accessible.');
        }
        
        //Load the config
        $config = Zend_Registry::get('config');
        
        /**
         * There shouldnt be any files in the archive that we dont want,
         * but just incase they slip in, make sure they dont get copied over.
         */
        $exclude_files = array(
           $config->public_path.'/install',
           '/application/database',
           '/application/logs',
           '/application/lucene',
        );
        
        $folder = $from.'/'.$expected_folder;
        
        //Get a list of all the files in the new version
        $all_files = $this->_getFileList($folder);
        
        if (is_array($all_files) && count($all_files) > 0)
        {
            /**
             * I know this seems slow and inefficient, but i think
             * its important to verify all the files before copying
             * any, to avoid issues with aborted upgrades.
             */
            foreach ($all_files as $path)
            {
                $sub_path    = substr($path, strlen($folder));
                $should_skip = false;
                
                foreach ($exclude_files as $exclude_file)
                {
                    if (substr($sub_path, 0, strlen($exclude_file)) == $exclude_file)
                    {
                        //Skip this file
                        $should_skip = true;
                        break;
                    }
                }
                
                if ($should_skip === true) {
                    continue;
                }
                
                //Make sure the file is in the receipts
                $file_hash = sha1_file($path);
                $file_key  = sha1($file_hash.$sub_path);
                
                if (array_key_exists($file_key, $path_receipts))
                {
                    //Get the details about the file
                    $receipt = $path_receipts[$file_key];
                    
                    //Verify the hash
                    if ($receipt['hash'] != $file_hash)
                    {
                        throw new Bugify_Exception(sprintf('Hashes do not match on file "%s"', $sub_path));
                    }
                }
            }
            
            /**
             * Now go through the same process, but this time do
             * the copying.
             */
            foreach ($all_files as $path)
            {
                $sub_path    = substr($path, strlen($folder));
                $should_skip = false;
                
                foreach ($exclude_files as $exclude_file)
                {
                    if (substr($sub_path, 0, strlen($exclude_file)) == $exclude_file)
                    {
                        //Skip this file
                        $should_skip = true;
                        break;
                    }
                }
                
                if ($should_skip === true) {
                    continue;
                }
                
                //Make sure the file is in the receipts
                $file_hash = sha1_file($path);
                $file_key  = sha1($file_hash.$sub_path);
                
                if (array_key_exists($file_key, $path_receipts))
                {
                    //Get the details about the file
                    $receipt = $path_receipts[$file_key];
                    
                    //Verify the hash
                    if ($receipt['hash'] != $file_hash)
                    {
                        throw new Bugify_Exception(sprintf('Hashes do not match on file "%s"', $sub_path));
                    }
                    
                    //Check if the file should be executable
                    $exec = ($receipt['exec'] == 1) ? true : false;
                    
                    //Work out the destination path
                    $to_path = $to.$sub_path;
                    
                    //Make sure the destination folder exists
                    $dir = dirname($to_path);
                    
                    if (!is_dir($dir))
                    {
                        if (mkdir($dir, 0755, true) === false)
                        {
                            throw new Bugify_Exception('Could not create the folder '.$dir);
                        }
                    }
                    
                    if (copy($path, $to_path) === true)
                    {
                        if ($exec === true)
                        {
                            //Make sure the copied file is executable also
                            chmod($to_path, 0755);
                        }
                    }
                    else
                    {
                        throw new Bugify_Exception(sprintf('Could not copy %s', $sub_path));
                    }
                }
            }
        }
        else
        {
            throw new Bugify_Exception('The upgrade did not contain any files.');
        }
    }
    
    private function _getFileList($folder)
    {
        $files = array();
        
        if (is_dir($folder))
        {
            foreach (scandir($folder) as $item)
            {
                if ($item == '.' || $item == '..')
                {
                    continue;
                }
                
                $files = array_merge($files, $this->_getFileList($folder.'/'.$item));
            }
        }
        else
        {
            $files[] = $folder;
        }
        
        return $files;
    }
    
    public function shouldCheckForUpgrade() {
        //Check if enough time has passed since the last check
        return ($this->_last_checked < (time()-$this->_check_freshness));
    }
    
    public function checkForUpgrade($force=false) {
        //Check if enough time has passed since the last check
        if ($this->shouldCheckForUpgrade() || $force === true) {
            //Enough time has passed since the last check so check again now
            $this->_checkForUpgrade();
        }
    }
    
    public function checkForUpgradeAsync($force=false) {
        /**
         * Use the cli script to check for updates to avoid locking up
         * the interface if the update server is unreachable.
         */
        $params = ($force === true) ? '-c -f' : '-c';
        
        //Work out the path to the cli tool
        $config  = Zend_Registry::get('config');
        $path    = realpath(sprintf('%s/cli/tools/update-software', $config->base_path));
        $command = sprintf('php %s %s > /dev/null 2>&1 &', $path, $params);
        $output  = array();
        $status  = null;
        $result  = exec($command, $output, $status);
        
        if ($status != 0) {
            throw new Bugify_Exception($result);
        }
    }
    
    public function getLastChecked()
    {
        return Bugify_Date::getLocalTime($this->_last_checked);
    }
    
    public function getNextCheck()
    {
        return Bugify_Date::getLocalTime(($this->_last_checked + $this->_check_freshness));
    }
    
    public function upgradeExists()
    {
        return version_compare($this->_available['version'], Bugify_Version::VERSION, '>');
    }
    
    public function getUpgradeInfo()
    {
        return $this->_available;
    }
    
    public function doUpgrade()
    {
        //Force an upgrade check now to make sure we have the latest info
        $this->_checkForUpgrade();
        
        //Make sure an upgrade exists
        if ($this->upgradeExists())
        {
            try
            {
                //Set the timeout length high to handle slow downloads
                set_time_limit(0);
                
                //Put the app into maintenance mode
                $this->_beginMaintenanceMode();
                
                //Download the update
                $this->_downloadAndDecompressUpdate();
                
                //Post-upgrade cleanup
                $c = new Bugify_Upgrades_Cleanup();
                $c->removeOldFiles();
                
                //Do a database upgrade
                $u = new Bugify_Db_Upgrade();
                $u->upgradeDbSchema();
                
                //Take the app out of maintenance mode
                $this->_endMaintenanceMode();
                
                /**
                 * Reset the last checked date so the app forces
                 * a check at next load.  This means that the upgrade
                 * server is aware that the upgrade just happened.
                 */
                $this->_last_checked = 0;
                $this->_save();
            }
            catch (Exception $e)
            {
                //Make sure the app is taken out of maintenance mode
                $this->_endMaintenanceMode();
                
                throw $e;
            }
        }
        else
        {
            throw new Bugify_Exception('There are no upgrades available to be installed.');
        }
    }
}
