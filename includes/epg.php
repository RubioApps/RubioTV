<?php
/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.3.0                                                           |
 |                                                                         |
 | This program is free software: you can redistribute it and/or modify    |
 | it under the terms of the GNU General Public License as published by    |
 | the Free Software Foundation.                                           |
 |                                                                         |
 | This file forms part of the RubioTV software.                           |
 |                                                                         |
 | If you wish to use this file in another project or create a modified    |
 | version that will not be part of the RubioTV Software, you              |
 | may remove the exception above and use this source code under the       |
 | original version of the license.                                        |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the            |
 | GNU General Public License for more details.                            |
 |                                                                         |
 | You should have received a copy of the GNU General Public License       |
 | along with this program.  If not, see http://www.gnu.org/licenses/.     |
 |                                                                         |
 +-------------------------------------------------------------------------+
 | Author: Jaime Rubio <jaime@rubiogafsi.com>                              |
 +-------------------------------------------------------------------------+
*/
namespace RubioTV\Framework;

defined('_TVEXEC') or die;

use RubioTV\Framework\Factory; 
use RubioTV\Framework\IPTV; 
use RubioTV\Framework\SEF; 
use RubioTV\Framework\M3U; 


define ('CRON_BATCH_LIMIT', 5);

class EPG{        
    protected $url;      
    protected $tvg_id;
    protected $params;
    
    public function __construct($url = null)
    {        
        $config         = Factory::getConfig();

        $this->params = (array) $config->epg;
        $this->url    = $url;
    }

    public function __destruct() {
    }   

    /**
     * getGuide
     * 
     * @param string $tvg_id Official channel ID
     * 
     * @return mixed It returns null when there is no EPG available, 
     * and an two-dimensions array, where the first item is an array with the timed EPG and a second the element playing right now
     */
    public function getGuide( $tvg_id )
    {
        $config         = Factory::getConfig();
        $this->tvg_id   = $tvg_id;        
                         
        if(!$this->url)
            return null;

        // Get remote content
        $context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));                
        $xml = file_get_contents($this->url, false, $context);
        
        if ($xml !== false)
        {
            $xml = simplexml_load_string($xml);
            if($list = $xml->xpath('/tv/programme[@channel="' . $this->tvg_id . '"]'))
            {              
                $ret        = [];
                $current    = null;
                foreach($list as $p)
                {                   
                    // Initicalize the object 
                    $item           = new \stdClass();                                                                 
                    $item->title    = '';
                    $item->subtitle = '';
                    $item->desc     = '';
                    $item->category = '';
                    $item->playnow  = false;
                    
                    // Extract the subnodes
                    foreach($p->children() as $k => $v){                        
                        $prop = preg_replace('/[^a-z]+/','',$k);
                        $item->$prop = sprintf("%s",$v);
                    } 

                    // Prepare the timezone
                    $fix    = new \DateInterval(sprintf("PT%dH",$config->epg['fix_time'])); 
                    $ival   = new \DateInterval('PT3H');         
                    $tz     = new \DateTimeZone(date_default_timezone_get());   
                    $now    = new \DateTime();  

                    // Extract the timeslot
                    $attr       = $p->attributes();                                      
                    $item->start= (new \DateTime( $attr['start'] ))->setTimezone($tz)->sub($fix);
                    $item->end  = (new \DateTime( $attr['stop'] ))->setTimezone($tz)->sub($fix);                                                                            

                    if($item->start <= $now && $now <= $item->end && $current === null){                      
                        $item->playnow = true;
                        $current = $item;
                    }                    

                    //Only add for items after now - 3 hours                    
                    $pid    = md5($attr['start'] .$attr['channel']  );
                    if($item->start >= $now->sub($ival) && !isset($ret[$pid]))
                        $ret[$pid ] = $item;                                          

                    unset($tz ,$fix , $now, $ival , $item);
                }
                return array($ret , $current);
            }
        }
        return null;

    }

    /**
     * This function works for the XMLTV provided by TvHeadend
     * The attribute id for TvHeadend replaced the standard tvg_id for XMLTV
     */
    public function getPlayingNow()
    {
        if($this->url === null)
            return false;

        $ret    = false;
        $config = Factory::getConfig();
    
        // Get remote content
        $context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));                
        $xml = file_get_contents($this->url, false, $context);
        
        if ($xml !== false)
        {
            $xml = simplexml_load_string($xml);
            //Get the channels
            if($list = $xml->xpath('/tv/channel'))
            {  
                $channels = [];
                foreach($list as $c)
                {               
                    $attr       = $c->attributes();   
                    $id         = sprintf("%s",$attr->id);

                    $item       = new \stdClass();  
                    $item->id   = $id;

                    foreach($c->children() as $k => $v)
                    {       
                        $prop = preg_replace('/[^a-z]+/','',$k);
                        if($prop === 'icon')                        
                            $item->icon = $v->attributes()->src->__toString();                

                        if(empty($item->$prop)) $item->$prop = $v->__toString();                         
                    }                         
                    $channels[] = $item;                                                               
                }
            }

            //Get the programs
            if(!empty($channels))
            {   
                $ret = [];   
                foreach($channels as $c)
                {                 
                    $list = $xml->xpath('/tv/programme[@channel="' . $c->id . '"]');                             

                    foreach($list as $p)
                    {       
                        // Prepare the timezone
                        $fix    = new \DateInterval(sprintf("PT%dH",$config->epg['fix_time'])); 
                        $ival   = new \DateInterval('PT3H');         
                        $tz     = new \DateTimeZone(date_default_timezone_get());   
                        $now    = new \DateTime();

                        $attr   = $p->attributes();  

                        $item           = new \stdClass();     
                        $item->id       = $c->id;
                        $item->name     = $c->displayname;
                        $item->icon     = $c->icon;
                        $item->start    = (new \DateTime( $attr['start'] ))->setTimezone($tz)->sub($fix);
                        $item->end      = (new \DateTime( $attr['stop'] ))->setTimezone($tz)->sub($fix);                                
                        $item->title    = '';
                        $item->subtitle = '';
                        $item->desc     = '';
                        $item->category = '';

                        //Calculate the progress
                        $duration   = $item->end->getTimeStamp() - $item->start->getTimestamp();
                        $lapse      = $now->getTimeStamp() - $item->start->getTimestamp();
                        $item->progress = $duration>0 ? sprintf("%d" , 100 * ($lapse/$duration)) : 0;

                        if($lapse < 60)
                            $item->viewed = sprintf("%ds." , $lapse);
                        elseif($lapse < 3600)
                            $item->viewed = sprintf("%dm." , $lapse / 60);
                        else
                            $item->viewed = sprintf("%dh. %dm." , $lapse / 3600 , ($lapse % 3600)/60);

                        // Only add the items playing now
                        if($item->start <= $now && $item->end >= $now){
                            foreach($p->children() as $k => $v){                        
                                $prop = preg_replace('/[^a-z]+/','',$k);
                                $item->$prop = $v->__toString();
                            }   

                            if(!isset($ret[$item->id]))                               
                                $ret[$item->id] = $item;               
                        }  

                        unset($tz ,$fix , $now, $ival , $item);                   
                    }
                }
                return $ret;
            }
        }
        return false;
    }   

    /**
     * getSiteFromXMLTV
     * 
     * @param string $filename  The full path to the XMLTV file
     * 
     * @return bool It returns the site name where the EPG is available, or false if there is no EPG available.
     */
    public function getSiteFromXMLTV($filename)
    {            
        $config = Factory::getConfig(); 
        
        // Check if the file exists. Otherwise, create it
        if(!file_exists($filename))
        {
            return false;
        } 

        // Load the file of requests, and query the root and the channel
        
        $xml    = simplexml_load_file($filename);
        $root   = $xml->xpath('//channels');                

        if($root)
        {
            $query  = $xml->xpath('//channels/channel');
            if($query)
            {              
                foreach($query[0]->attributes() as $k => $v)
                {                      
                    if($k === 'site')
                    {
                        return $v;
                    }
                }
            }             
        }     
        return false;      
    }

    /**
     * getXMLTV
     * 
     * @param string $fileid Internal channel id of the program (md5 hash of the tvg_id)
     * @param string $tvg_id official id from the channel, as published in the EPG
     * 
     * @return mixed Returns false is the function did not find a convenient guide or the creation failed. 
     * Returns true if the file was not found but the request was put in the queue. In other cases, it returns the name of the EPG file
     */
    public function getXMLTV( $fileid   , $tvg_id)
    {            
        $config = Factory::getConfig(); 
        $guides = IPTV::getGuides();
        
        $this->tvg_id = $tvg_id;        

        foreach($guides as $g)
        {
            if($g->channel === $this->tvg_id)
            {
                $xmltv = $g;
                break;
            }
        }
       
        if(!empty($xmltv))
        {         
            if(!file_exists(TV_EPG_SAVED. DIRECTORY_SEPARATOR . $fileid . '.xml'))
            {                                
                //Add this channel to the pending list    
                if($this->_requestXMLTV($fileid , $xmltv) === false)
                    throw new \Exception('Malformed file of missing XMLTV');
                return true;
            } else {                
                return $config->live_site . '/epg/saved/' . $fileid . '.xml';
            }
        }
        return false;
    }    

    public function MergeXMLTV( $folder = 'custom' , $source = 'playlist')
    {
        $config     = Factory::getConfig();
        $now        = new \DateTime();
        $channels   = [];
        $programs   = [];
     
        // Target file
        $merged = TV_EPG . DIRECTORY_SEPARATOR . $folder . '.' . $source . '.xmltv';        
        
        // Check whether the file might be obsolete (renewed every 5 min max.)
        if(file_exists($merged))
        {
            if(filectime($merged) > $now->getTimestamp() - 300){
                $this->url = $merged;
                return true;
            }
        }        

        switch($folder){
            case 'countries':
            case 'categories':                
            case 'languages':                
                $url = SEF::find($folder , $source);
                break;  
            case 'custom':
                $url = $config->live_site . '/custom/' . $source . '.m3u';
                break;
            case 'dtv':                 
                $url = $config->dtv['host'] . $config->dtv['channels'];
                break;
            default:                              
                return false;
        }  

        //Get the DTV Guide whatever the folder is
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config->dtv['host'] . $config->dtv['channels']);  
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_exec($ch); 

        if(!curl_errno($ch))
        {         
            $content    = file_get_contents($config->dtv['host'] . $config->dtv['xmltv']);
            $xml_dtv    = simplexml_load_string($content);           
        } else {
            $xml_dtv    = null;
        }
        curl_close($ch); 
        
        // Collect the list of channels in the given source
        $filem3u = new M3U($folder , $source , $url);
        $data = $filem3u->load(); 

        // For each channel found, we store the guide
        foreach($data as $item)
        {
            // IPTV
            if(strstr($item->url , $config->dtv['host']) === false)
            {
                if(file_exists(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $item->id . '.xml'))
                {
                    $xml = simplexml_load_file(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $item->id . '.xml');
                    $channels[$item->id] = $xml->xpath('//tv/channel');
                    $programs[$item->id] = $xml->xpath('//tv/programme');                    
                }
            // DTV
            } else {
                if($xml_dtv !== null)
                {                            
                    $channels[$item->id] = $xml_dtv->xpath('//tv/channel[@id="' . $item->id . '"]');
                    $programs[$item->id] = $xml_dtv->xpath('//tv/programme[@channel="' . $item->id . '"]');                                     
                }
            }

            // Standard Channel node: check the subnodes
            if(isset($channels[$item->id] ))
            {
                $fields    = ['display-name','icon','url'];
                foreach($channels[$item->id] as $c)
                {
                    $nodes = $c->children();                             

                    // Ensure the integrity of the channels, because DTV might not provide a standard output                    
                    foreach($fields as $f)
                    {                
                        if(!isset($count[$f]))
                            $count[$f] = 0;
                        if(!isset($nodes[$f]) && !empty($item->$f))
                            $c->addChild($f,$item->$f);                        
                    }  
                }     
            }

            // Standard Program node: check the attributes
            if(isset($programs[$item->id] ))
            {            
                $fields    = ['start','stop','channel'];
                foreach($programs[$item->id] as $p)
                {
                    $attr = $p->attributes();                              
                    
                    // Ensure the integrity of the programs, because DTV might not provide a standard output                    
                    foreach($fields as $f)
                    {                
                        if(!isset($count[$f]))
                            $count[$f] = 0;                        
                        if(!isset($attr[$f]) && !empty($item->$f))
                            $p->addAttribute($f, $item->$f);;                        
                    }                            
                }             
            }
        }

        if(file_exists($merged))
            unlink($merged);

        // Start the content
        $content    = '<?xml version="1.0" encoding="UTF-8" ?>' . PHP_EOL;
        $content   .= '<tv date="' . $now->format('Ymd'). '">' . PHP_EOL;       

        

        foreach($channels as $id => $c)
        {
            foreach($c as $node)
            {    
                $node->attributes()->id = $id;
                if(file_exists(TV_CACHE . DIRECTORY_SEPARATOR . $id . '.png'))
                {
                    $node->xpath('icon')[0]->attributes()->src = $config->live_site . '/cache/' . $id . '.png';
                }

                $content .= trim(preg_replace('/\s+/', ' ', $node->asXML())) . PHP_EOL;
            }
        }

        foreach($programs as $id => $p)
        {
            foreach($p as $node)
            {
                $node->attributes()->channel = $id;
                $content .= trim(preg_replace('/\s+/', ' ', $node->asXML())) . PHP_EOL;           
            }
        }
        $content .= '</tv>' . PHP_EOL;
        
        // Save the result
        $fp = fopen($merged,'w+');
        flock($fp,LOCK_EX);
        fwrite($fp, $content);  
        fclose($fp);   
        
        $this->url = $merged;
        return true;
    }
    

    protected function _requestXMLTV($fileid , $xmltv)
    {            
        //Add this channel to the pending list    
        $pending    = TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $fileid . '.xml';

        // Check if the file exists. Otherwis, create it
        if(!file_exists($pending))
        {
            $header = <<<XML
            <?xml version="1.0" encoding="UTF-8" ?>
                <channels></channels>
            XML;
            $xml = new \SimpleXMLElement($header);                    
            $xml->asXML($pending);
            unset($xml);
        } 

        // Load the file of requests, and query the root and the channel
        $xml    = simplexml_load_file($pending);
        $root   = $xml->xpath('//channels');                

        if($root && $xmltv->channel)
        {
            $query  = $xml->xpath('//channels/channel[@xmltv_id="' . $xmltv->channel . '"]');
            if(!$query)
            {                    
                $node = $root[0]->addChild('channel' , $xmltv->site_name);
                $node->addAttribute('site' , $xmltv->site); 
                $node->addAttribute('lang' , $xmltv->lang); 
                $node->addAttribute('xmltv_id' , $xmltv->channel); 
                $node->addAttribute('site_id' , $xmltv->site_id);                
                $xml->asXML($pending);
            }             
            return true;
        }     
        return false;      
    }

    /**
     * Cron to collect the EPG
     * NOTE: You'd need to edit the /etc/sudoers file to allow the apache user (www-data) to execute a script
     * 
     * 1. edit file : /etc/sudoers
     * 2. add a line: www-data ALL=NOPASSWD: /var/www/mysite/public/epg/saved/.unlock
     * 3. add a cron to run the task every 5 minutes (recommended)
     *      type        : cron -u www-data -e
     *      add a line  : "* /5 * * * * /usr/bin/php /var/www/mysite/public/cron.php       
     * 
     * @param mixed $id Internal channel ID (md5 hash of the tvg_id)
     * @param bool  $force This forces the cron to run, overriding the anti-flood mechanism
     * 
     * @return bool Return false if it fails an true if it succeeds
     */
    public function Cron( $id = null)
    {        
        if(!isset($this->params['enabled']))
            return false;
        
        if(!file_exists(TV_EPG_QUEUE))
            mkdir(TV_EPG_QUEUE , 0755 , true);

        if(!file_exists(TV_EPG_SAVED))
            mkdir(TV_EPG_SAVED , 0755 , true);

        $timestamp  = intval(microtime(1));            
        $lock       = TV_EPG . DIRECTORY_SEPARATOR . '.lock';

        // Run batch for custom channels
        if ($id === null)
        {
            if ($dir = opendir(TV_IPTV . DIRECTORY_SEPARATOR . 'custom'))
            {
                $config     = Factory::getConfig();     
                $guides     = IPTV::getGuides();
                $postponed  = [];
                $count  = 0;
        
		        while (($f = readdir($dir)) !== false)
		        {        
			        if ($f != '.' && $f != '..')
			        {
                        $filename   = TV_IPTV . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . $f;
				        $info = pathinfo($filename);
                        if($info['extension'] === 'm3u')
                        {
                            $url    = $config->live_site . '/iptv/custom/' . $info['basename'];
                            $m3u    = new M3U('custom' , $info['filename'] , $url);
                            $list   = $m3u->load();                                              

                            foreach($list as $item)                           
                            {                    
                                foreach($guides as $g)
                                {
                                    if($g->channel === $item->tvg_id)
                                    {      
                                        // Only do if there is no saved guide
                                        if($item->id && !file_exists(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $item->id . '.xml'))
                                        {        
                                            // If the guide is queued, postpone it
                                            if(file_exists(TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $item->id . '.xml'))
                                            {
                                                $postponed[] = $item;
                                            } else {                                                                
                                                // Request a guide
                                                $this->_requestXMLTV($item->id , $g);
                                                // Launch the cron
                                                $this->Cron($item->id);    
                                                // Increase the count only if succeeded
                                                if(file_exists(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $item->id . '.xml'))
                                                    $count++;
                                            }
                                        }   
                                        break;
                                    }
                                }    
                                // Exit if the limit is reached
                                if($count > CRON_BATCH_LIMIT)
                                    break;                                                                                                                                         
                            }
                        }
                    }
                }  
            }
            closedir($dir); 

            //Treat the postponed cronjobs if there is still any remaining place in this batch
            if(count($postponed) && $count <= CRON_BATCH_LIMIT)
            {
                foreach($postponed as $item)
                {                  
                    // Launch the cron
                    $this->Cron($item->id);    
                    // Increase the count only if succeeded
                    if(file_exists(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $item->id . '.xml'))
                        $count++;
                    // Exit if the limit is reached
                    if($count > CRON_BATCH_LIMIT)
                        break;                      
                }
            }
            return true;          
        }

	    // Check whether the files are now expired
	    if ($dir = opendir(TV_EPG_SAVED))
	    {				
		    while (($f = readdir($dir)) !== false)
		    {        
			    if ($f != '.' && $f != '..')
			    {
				    if(!is_dir(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $f))
				    {
					    $last = filectime(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $f);
					    if($last <= $timestamp - (86400 * (int)$this->params['expiry']))
    						unlink(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $f);
				    }
			    }
		    }
            closedir($dir);
	    }

        // Let('s browse the queue)
	    if ($id && $dir = opendir(TV_EPG_QUEUE))
	    {		
		    // Check if the enqueued file has been already processed
		    while (($f = readdir($dir)) !== false)
		    {        
			    if ($f != '.' && $f != '..')
			    {
				    // If already process, remove the enqueued file
				    if(file_exists(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $f))
				    {
					    unlink(TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $f);
					    continue;
				    }
				
				    // If the file was not processed during the last cron , then rip it off				                    
				    if(file_exists($lock))
				    {			
					    if(filectime(TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $f) <= filectime($lock) - 300)
					    {					
						    unlink(TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $f);
						    continue;
					    }
				    }
			    }
		    }
            closedir($dir); 

            // Check lock validity
		    if(file_exists($lock))
		    {			
			    $valid = ( filectime($lock) < $timestamp - (int) $this->params['lock']);					
		    } else {
    			$valid = true;
		    }		
          
		    if($valid)
		    {
    			// Remove the lock
                if(file_exists($lock))
			        unlink($lock);

			    // npm works with relative directories. So, let's get the trails
			    $relative	= '';
			    $path		= explode(DIRECTORY_SEPARATOR , $this->params['dir']);		
			    for($i = 0 ; $i < count($path) - 1; $i++){
    				$relative = '..' . DIRECTORY_SEPARATOR . $relative;
			    }

			    $expiry 	= (int) $this->params['expiry'];
                $username   = exec('whoami');

			    // Create the content of the bash script
                $script  = '#!/bin/bash' . PHP_EOL;
                $script	.= 'cd ' . $this->params['dir'] . PHP_EOL;	

                
                $channels	= $relative . TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $id . '.xml';
                $output		= $relative . TV_EPG_SAVED . DIRECTORY_SEPARATOR . $id . '.xml';	
			    
                $script .=  sprintf($this->params['exec'] ,  $this->params['dir'], $channels , $output, $expiry) . 
                    ' --delay=1000 --timeout=3000 --maxConnections=10 > /dev/null 2>&1' . PHP_EOL;
                $script .= 'if [ -e ' . $id . '.xml ] ' . PHP_EOL;
                $script .= 'then ' . PHP_EOL;
                $script .= 'chmod 0755 ' . TV_EPG_SAVED . DIRECTORY_SEPARATOR . $id . '.xml' . PHP_EOL;                    
                $script .= 'chown ' . $username . ':' . $username . ' ' . TV_EPG_SAVED . DIRECTORY_SEPARATOR . $id . '.xml' . PHP_EOL;
                $script .= 'fi' . PHP_EOL;
                                 
			    // Save the bash script
			    $bash = TV_EPG . DIRECTORY_SEPARATOR . '.unlock';

			    // Avoid to have an old file
			    if(file_exists($bash))
    				unlink($bash);

			    // Put the content in 
                $fp = fopen($bash , 'w+');
                flock($fp,LOCK_EX);
                fwrite($fp, $script);                
                fclose($fp);

                // Turn into executable
                chmod($bash, 0755);

			    // Execute the bash                
                exec('sudo ' . $bash, $output);
                //error_log(print_r($output,true));

			    // Remove the script for security reasons
			    if(file_exists($bash))
    				unlink($bash);		
                   
			    // Lock the cron
                $this->Lock();

                // Return result
                return true;
		    }
	    }
        return false;
    }    

    public function Unlock($key)
    {
        // Check the key is correct
        $cron_id = $this->_Decrypt($key);                
        
        if($cron_id !== $_SESSION['cron_id'])
            return false;
            
        //Ensure the cron id is destroyed
        unset($_SESSION['cron_id']);

        //Remove the lock
        $lock = TV_EPG . DIRECTORY_SEPARATOR . '.lock';
        if(file_exists($lock))
            unlink($lock);

        return true;
    }

    public function Lock()
    {
        $_SESSION['cron_id'] = $this->_generateCronId();

        $lock = TV_EPG . DIRECTORY_SEPARATOR . '.lock';

        if(file_exists($lock))
            unlink($lock);

        touch($lock);
    }    
    public function getCronId()
    {
        $_SESSION['cron_id'] = $this->_generateCronId();
        return $this->_Encrypt($_SESSION['cron_id']);        
    }    

    protected function _generateCronId($length = 32)
    {
        return substr(bin2hex(openssl_random_pseudo_bytes(ceil($length / 2))), 0, $length);
    }

    protected function _Encrypt($string)
    {              
        $encrypt_method = 'AES-256-CBC';                
        $secret_iv      = '$kn{/R5|xrhq_hPZ';  
        $secret_key     = $this->params['secret_key'];            
            
        // hash
        $key = hash('sha256', $secret_key);
            
        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);
         
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        return base64_encode($output);       
    }

    protected function _Decrypt($string)
    {              
        $encrypt_method = 'AES-256-CBC';                
        $secret_iv      = '$kn{/R5|xrhq_hPZ';  
        $secret_key     = $this->params['secret_key'];            
            
        // hash
        $key = hash('sha256', $secret_key);
            
        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        return openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);       
    }
}

