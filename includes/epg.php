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

define('CRON_BATCH_LIMIT', 10);

class EPG
{
    protected $url;
    protected $tvg_id;
    protected $params;
    protected $queued;
    protected $lockfile;
    protected $islocked;

    public function __construct($url = null)
    {
        $config         = Factory::getConfig();
        $this->lockfile = TV_EPG . DIRECTORY_SEPARATOR . '.lock';
        $this->params = (array) $config->epg;
        $this->url    = $url;
        $this->queued = []; 
    }

    public function __destruct() {}

    /**
     * getGuide
     * 
     * @param string $tvg_id Official channel ID
     * 
     * @return mixed It returns null when there is no EPG available, 
     * and an two-dimensions array, where the first item is an array with the timed EPG and a second the element playing right now
     */
    public function getGuide($tvg_id)
    {
        $config         = Factory::getConfig();
        $this->tvg_id   = $tvg_id;

        if (!$this->url)
            return null;

        // Get remote content
        $context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
        $xml = file_get_contents($this->url, false, $context);

        if ($xml !== false) {
            $xml = simplexml_load_string($xml);
            if ($list = $xml->xpath('/tv/programme[@channel="' . $this->tvg_id . '"]')) {
                $ret        = [];
                $current    = null;
                foreach ($list as $p) {
                    // Initicalize the object 
                    $item           = new \stdClass();
                    $item->title    = '';
                    $item->subtitle = '';
                    $item->desc     = '';
                    $item->category = '';
                    $item->playnow  = false;

                    // Extract the subnodes
                    foreach ($p->children() as $k => $v) {
                        $prop = preg_replace('/[^a-z]+/', '', $k);
                        $item->$prop = sprintf("%s", $v);
                    }

                    // Prepare the timezone
                    $fix    = new \DateInterval(sprintf("PT%dH", $config->epg['offset']));
                    $ival   = new \DateInterval('PT3H');
                    $tz     = new \DateTimeZone(date_default_timezone_get());
                    $now    = new \DateTime();

                    // Extract the timeslot
                    $attr       = $p->attributes();
                    $item->start = (new \DateTime($attr['start']))->setTimezone($tz)->sub($fix);
                    $item->end  = (new \DateTime($attr['stop']))->setTimezone($tz)->sub($fix);

                    if ($item->start <= $now && $now <= $item->end && $current === null) {
                        $item->playnow = true;
                        $current = $item;
                    }

                    //Only add for items after now - 3 hours                    
                    $pid    = md5($attr['start'] . $attr['channel']);
                    if ($item->start >= $now->sub($ival) && !isset($ret[$pid]))
                        $ret[$pid] = $item;

                    unset($tz, $fix, $now, $ival, $item);
                }
                return array($ret, $current);
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
        if ($this->url === null)
            return false;

        $ret    = false;
        $config = Factory::getConfig();

        // Get remote content
        $context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
        $xml = file_get_contents($this->url, false, $context);

        if ($xml !== false) {
            $xml = simplexml_load_string($xml);
            //Get the channels
            if ($list = $xml->xpath('/tv/channel')) {
                $channels = [];
                foreach ($list as $c) {
                    $attr       = $c->attributes();
                    $id         = sprintf("%s", $attr->id);

                    $item       = new \stdClass();
                    $item->id   = $id;

                    foreach ($c->children() as $k => $v) {
                        $prop = preg_replace('/[^a-z]+/', '', $k);
                        if ($prop === 'icon')
                            $item->icon = $v->attributes()->src->__toString();

                        if (empty($item->$prop)) $item->$prop = $v->__toString();
                    }
                    $channels[] = $item;
                }
            }

            //Get the programs
            if (!empty($channels)) {
                $ret = [];
                foreach ($channels as $c) {
                    $list = $xml->xpath('/tv/programme[@channel="' . $c->id . '"]');

                    foreach ($list as $p) {
                        // Prepare the timezone
                        $fix    = new \DateInterval(sprintf("PT%dH", $config->epg['offset']));
                        $ival   = new \DateInterval('PT3H');
                        $tz     = new \DateTimeZone(date_default_timezone_get());
                        $now    = new \DateTime();

                        $attr   = $p->attributes();

                        $item           = new \stdClass();
                        $item->id       = $c->id;
                        $item->name     = $c->displayname;
                        $item->url      = $c->url;
                        $item->logo     = $c->icon ?? Factory::getAssets() . '/images/notfound.png';
                        $item->remote   = $c->icon ?? '';
                        $item->start    = (new \DateTime($attr['start']))->setTimezone($tz)->sub($fix);
                        $item->end      = (new \DateTime($attr['stop']))->setTimezone($tz)->sub($fix);
                        $item->title    = '';
                        $item->subtitle = '';
                        $item->desc     = '';
                        $item->category = '';

                        //Calculate the progress
                        $duration   = $item->end->getTimeStamp() - $item->start->getTimestamp();
                        $lapse      = $now->getTimeStamp() - $item->start->getTimestamp();
                        $item->progress = $duration > 0 ? sprintf("%d", 100 * ($lapse / $duration)) : 0;

                        if ($lapse < 60)
                            $item->viewed = sprintf("%ds.", $lapse);
                        elseif ($lapse < 3600)
                            $item->viewed = sprintf("%dm.", $lapse / 60);
                        else
                            $item->viewed = sprintf("%dh. %dm.", $lapse / 3600, ($lapse % 3600) / 60);

                        // Only add the items playing now
                        if ($item->start <= $now && $item->end >= $now) {
                            foreach ($p->children() as $k => $v) {
                                $prop = preg_replace('/[^a-z]+/', '', $k);
                                $item->$prop = $v->__toString();
                            }

                            if (!isset($ret[$item->id]))
                                $ret[$item->id] = $item;
                        }

                        unset($tz, $fix, $now, $ival, $item);
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
        if (!file_exists($filename)) {
            return false;
        }

        // Load the file of requests, and query the root and the channel

        $xml    = simplexml_load_file($filename);
        $root   = $xml->xpath('//channels');

        if ($root) {
            $query  = $xml->xpath('//channels/channel');
            if ($query) {
                foreach ($query[0]->attributes() as $k => $v) {
                    if ($k === 'site') {
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
    public function getXMLTV($fileid, $tvg_id)
    {
        $config = Factory::getConfig();
        $guides = IPTV::getGuides();

        $this->tvg_id = $tvg_id;

        foreach ($guides as $g) {
            if ($g->channel === $this->tvg_id) {
                $xmltv = $g;
                break;
            }
        }

        if (!empty($xmltv)) {
            if (!file_exists(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $fileid . '.xml')) {
                //Add this channel to the pending list    
                if ($this->_createRequest($fileid, $xmltv) === false)
                    throw new \Exception('Malformed file of missing XMLTV');
                return true;
            } else {
                return $config->live_site . '/epg/saved/' . $fileid . '.xml';
            }
        }
        return false;
    }

    public function MergeXMLTV($folder = 'custom', $source = 'playlist')
    {
        $config     = Factory::getConfig();
        $now        = new \DateTime();
        $channels   = [];
        $programs   = [];

        // Target file
        $merged = TV_EPG . DIRECTORY_SEPARATOR . $folder . '.' . $source . '.xmltv';

        // Check whether the file might be obsolete (renewed every 5 min max.)
        if (file_exists($merged)) {
            if (filemtime($merged) < time() - 300) {
                unlink($merged);
            } else {
                $this->url = $merged;
                return true;
            }
        }

        switch ($folder) {
            case 'countries':
            case 'categories':
            case 'languages':
                $url = SEF::find($folder, $source);
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

        if (!curl_errno($ch)) {
            $content    = file_get_contents($config->dtv['host'] . $config->dtv['xmltv']);
            $xml_dtv    = simplexml_load_string($content);
        } else {
            $xml_dtv    = null;
        }
        curl_close($ch);

        // Collect the list of channels in the given source
        $filem3u = new M3U($folder, $source, $url);
        $data = $filem3u->load();

        // For each channel found, we store the guide
        foreach ($data as $item) {
            // IPTV
            if (strstr($item->url, $config->dtv['host']) === false) {
                if (file_exists(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $item->id . '.xml')) {
                    $xml = simplexml_load_file(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $item->id . '.xml');
                    $channels[$item->id] = $xml->xpath('//tv/channel');
                    $programs[$item->id] = $xml->xpath('//tv/programme');
                }
                // DTV
            } else {
                if ($xml_dtv !== null) {
                    $channels[$item->id] = $xml_dtv->xpath('//tv/channel[@id="' . $item->id . '"]');
                    $programs[$item->id] = $xml_dtv->xpath('//tv/programme[@channel="' . $item->id . '"]');
                }
            }

            // Standard Channel node: check the subnodes
            if (isset($channels[$item->id])) {
                $fields    = ['display-name', 'icon', 'url'];
                foreach ($channels[$item->id] as $c) {
                    $nodes = $c->children();

                    // Ensure the integrity of the channels, because DTV might not provide a standard output                    
                    foreach ($fields as $f) {
                        if (!isset($count[$f]))
                            $count[$f] = 0;
                        if ($f && !isset($nodes[$f]) && !empty($item->$f))
                            $c->addChild($f, htmlspecialchars($item->$f));
                    }
                }
            }

            // Standard Program node: check the attributes
            if (isset($programs[$item->id])) {
                $fields    = ['start', 'stop', 'channel'];
                foreach ($programs[$item->id] as $p) {
                    $attr = $p->attributes();

                    // Ensure the integrity of the programs, because DTV might not provide a standard output                    
                    foreach ($fields as $f) {
                        if (!isset($count[$f]))
                            $count[$f] = 0;
                        if (!isset($attr[$f]) && !empty($item->$f))
                            $p->addAttribute($f, $item->$f);;
                    }
                }
            }
        }

        // Start the content
        $content    = '<?xml version="1.0" encoding="UTF-8" ?>' . PHP_EOL;
        $content   .= '<tv date="' . $now->format('Ymd') . '">' . PHP_EOL;

        foreach ($channels as $id => $c) {
            foreach ($c as $node) {
                $node->attributes()->id = $id;

                if (!file_exists(TV_CACHE_CHANNELS . DIRECTORY_SEPARATOR . $id . '.png')) {
                    //Set the paths
                    $path   = parse_url($node->xpath('icon')[0]->attributes()->src, PHP_URL_PATH);
                    $url    = $config->dtv['host'] . $path;

                    //Check the remote icon
                    $ch     = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $blob   = curl_exec($ch);

                    //Found, then copy blob into the cache
                    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200 && file_exists(TV_CACHE_CHANNELS . DIRECTORY_SEPARATOR . $id . '.png')) {
                        $fp = fopen(TV_CACHE_CHANNELS . DIRECTORY_SEPARATOR . $id . '.png', 'rw');
                        fwrite($fp, $blob);
                        fclose($fp);

                        //Not found, then put a dummy png into the cache
                    } else {
                        $source = Factory::getTheme() .
                            DIRECTORY_SEPARATOR . 'assets' .
                            DIRECTORY_SEPARATOR . 'images' .
                            DIRECTORY_SEPARATOR . 'notfound.png';
                        $target = TV_CACHE_CHANNELS . DIRECTORY_SEPARATOR . $id . '.png';
                        copy($source, $target);
                    }
                }
                $node->xpath('icon')[0]->attributes()->src = $config->live_site . '/cache/channels/' . $id . '.png';
                $content .= trim(preg_replace('/\s+/', ' ', $node->asXML())) . PHP_EOL;
            }
        }

        foreach ($programs as $id => $p) {
            foreach ($p as $node) {
                $node->attributes()->channel = $id;
                $content .= trim(preg_replace('/\s+/', ' ', $node->asXML())) . PHP_EOL;
            }
        }
        $content .= '</tv>' . PHP_EOL;

        // Save the result
        $fp = fopen($merged, 'w+');
        flock($fp, LOCK_EX);
        fwrite($fp, $content);
        fclose($fp);

        $this->url = $merged;
        return true;
    }


    /**
     * Create the file in the queue that contains the details of the EPG for a given channel
     * 
     * @param mixed $fileid Channel unique tag
     * @param mixed $xmltv  Channel as defined from the iptv-org EPG list
     * @return bool
     */
    protected function _createRequest($fileid, $xmltv)
    {
        //Add this channel to the pending list    
        $pending    = TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $fileid . '.xml';

        // Check if the file exists. Otherwise, create it
        if (!file_exists($pending)) {
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

        if ($root && $xmltv->channel) {
            $query  = $xml->xpath('//channels/channel[@xmltv_id="' . $xmltv->channel . '"]');
            if (!$query) {
                $node = $root[0]->addChild('channel', $xmltv->site_name);
                $node->addAttribute('site', $xmltv->site);
                $node->addAttribute('lang', $xmltv->lang);
                $node->addAttribute('xmltv_id', $xmltv->channel);
                $node->addAttribute('site_id', $xmltv->site_id);
                $xml->asXML($pending);
            }
            return true;
        }
        return false;
    }

    /**
     * 
     * Initialize the EPG 
     * @return bool
     */
    protected function _initEPG()
    {
        if (!isset($this->params['enabled']) || !$this->params['enabled'])
            return false;

        //Ensure the structure exists
        if (!file_exists(TV_EPG_QUEUE)) {
            mkdir(TV_EPG_QUEUE, 0750, true);
            $this->_debug(message: "Directory created: " . TV_EPG_QUEUE);
        }

        if (!file_exists(TV_EPG_SAVED)) {
            mkdir(TV_EPG_SAVED, 0750, true);
            $this->_debug("Directory created: " . TV_EPG_SAVED);
        }

        if (!file_exists(TV_EPG_EXPIRED)) {
            mkdir(TV_EPG_EXPIRED, 0750, true);
            $this->_debug("Directory created: " . TV_EPG_EXPIRED);
        }

        // Check whether the saved channels are now expired
        if ($dir = opendir(TV_EPG_SAVED)) {
            while (($f = readdir($dir)) !== false) {
                if ($f != '.' && $f != '..') {
                    if (!is_dir(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $f)) {
                        $last = filemtime(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $f);
                        if ($last < time() - (86400 * (int)$this->params['expiry']))
                            unlink(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $f);
                    }
                }
            }
            closedir($dir);
        }
        return true;
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
     * @return bool Return false if it fails an true if it succeeds
     */
    public function Cron()
    {
        if (!$this->_initEPG()) return false;

        $this->_debug("Run the batch of custom channels...");

        if ($dir = opendir(TV_IPTV . DIRECTORY_SEPARATOR . 'custom')) {
            $config     = Factory::getConfig();
            $guides     = IPTV::getGuides();

            while (($f = readdir($dir)) !== false) {
                if ($f != '.' && $f != '..') {
                    $filename   = TV_IPTV . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . $f;
                    $info = pathinfo($filename);
                    if ($info['extension'] === 'm3u') {
                        $url    = $config->live_site . '/iptv/custom/' . $info['basename'];
                        $m3u    = new M3U('custom', $info['filename'], $url);
                        if ($list   = $m3u->load()) {
                            $this->_debug("Custom list " . $info['basename'] . " has " . count($list) . " items");

                            foreach ($list as $item) {
                                foreach ($guides as $g) {
                                    //Check if the current channel exists among existing guide from iptv-org
                                    if ($g->channel === $item->tvg_id) {
                                        // Do only if there is the guide is not saved and not expired
                                        if (
                                            $item->id
                                            && !file_exists(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $item->id . '.xml')
                                            && !file_exists(TV_EPG_EXPIRED . DIRECTORY_SEPARATOR . $item->id . '.xml')
                                        ) {
                                            // If the guide is not yet queued, create the request include it into the array to process
                                            if (!file_exists(TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $item->id . '.xml')) {
                                                $this->_debug("Get the guide for " . $g->channel);
                                                $this->_createRequest($item->id, $g);
                                            }
                                            $this->_debug("Channel: " . $g->channel . " queued");
                                            $this->queued[] = $item->id;
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        closedir($dir);

        //Complete with the queued requests
        $dir = opendir(TV_EPG_QUEUE);
        while (($f = readdir($dir)) !== false) {
            if ($f != '.' && $f != '..') {
                $info = pathinfo(TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $f);
                $this->queued[] = $info['filename'];
            }
        }

        //Process the queue            
        if (count($this->queued)) {
            //Limit the batch            
            $this->queued = array_slice($this->queued, 0, CRON_BATCH_LIMIT);
            $this->_debug(count($this->queued) . " channels to process");
            foreach ($this->queued as $id) {
                $key = $this->getCronId();
                $this->Unlock($key);
                $this->Process($id);
                $this->Lock();
            }
        }

        //Finally, merge the saved programs
        if ($dir = opendir(TV_IPTV . DIRECTORY_SEPARATOR . 'custom')) {
            while (($f = readdir($dir)) !== false) {
                if ($f != '.' && $f != '..') {
                    $ext = pathinfo(TV_IPTV . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . $f, PATHINFO_EXTENSION);
                    if ($ext == 'm3u') {
                        $filename = pathinfo(TV_IPTV . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . $f, PATHINFO_FILENAME);
                        $this->_debug($filename);
                        if (!$this->MergeXMLTV('custom', $filename))
                            return false;
                    }
                }
            }
        }
        return true;
    }

    /**     
     * Process the EPG for a given channel
     * 
     * @param mixed $id Channel id
     * @return bool If the file is processed
     */
    public function Process($id)
    {
        if (!$this->_initEPG()) return false;

        // Do not process saved channels        
        if (file_exists(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $id . '.xml')) {
            if (file_exists(TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $id . '.xml'))
                unlink(TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $id . '.xml');
            return true;
        }

        // Do not process expired channels        
        if (file_exists(TV_EPG_EXPIRED . DIRECTORY_SEPARATOR . $id . '.xml')) {
            if (file_exists(TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $id . '.xml'))
                unlink(TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $id . '.xml');
            return false;
        }

        // Ignore if the channel is not yet queued
        if (!file_exists(TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $id . '.xml')) {
            $this->_debug("The channel $id.xml is not queued yet");
            return $this->Cron();
        }

        // If already processed, remove the enqueued file
        if (file_exists(TV_EPG_SAVED . DIRECTORY_SEPARATOR . $id . '.xml')) {
            $this->_debug("$id.xml already processed. Remove from the queue");
            unlink(TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $id . '.xml');
            return true;
        }

        // If the queue has not been processed during the last cron, rip it off			                    
        if (filemtime(TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $id . '.xml') < time() -  (int) $this->params['lock']) {
            $this->_debug("$id.xml was not processed during the last cron. Move it to the expired list");
            rename(TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $id . '.xml', TV_EPG_EXPIRED . DIRECTORY_SEPARATOR . $id . '.xml');
            return false;
        }

        // Is the lock still valid?
        $canUnlock  = $this->readLock() < (time() - (int) $this->params['lock']);
        $this->_debug($canUnlock ? "Cron unlocked" : "Cron locked. Do not process $id.xml");

        // If the cron can process
        if ($canUnlock && !file_exists(TV_EPG_EXPIRED . DIRECTORY_SEPARATOR . $id . ".xml")) {

            $this->_debug("Process $id.xml");

            // npm works with relative directories. So, let's get the trails
            $relative    = '';
            $path        = explode(DIRECTORY_SEPARATOR, $this->params['dir']);
            for ($i = 0; $i < count($path) - 2; $i++) {
                $relative .= '..' . DIRECTORY_SEPARATOR;
            }
            $relative .= "..";

            $expiry     = (int) $this->params['expiry'];
            $username   = exec('whoami');

            // Create the content of the bash script
            $script  = '#!/bin/bash' . PHP_EOL;
            $script    .= 'cd ' . $this->params['dir'] . PHP_EOL;

            $channels    = $relative . TV_EPG_QUEUE . DIRECTORY_SEPARATOR . $id . '.xml';
            $output        = $relative . TV_EPG_SAVED . DIRECTORY_SEPARATOR . $id . '.xml';

            $script .=  sprintf($this->params['exec'],  $this->params['dir'], $channels, $output, $expiry) .
                ' --delay=1000 --timeout=3000 --maxConnections=10 2>&1' . PHP_EOL;
            $script .= 'if [ -e ' . $id . '.xml ] ' . PHP_EOL;
            $script .= 'then ' . PHP_EOL;
            $script .= 'chmod 0750 ' . TV_EPG_SAVED . DIRECTORY_SEPARATOR . $id . '.xml' . PHP_EOL;
            $script .= 'chown ' . $username . ':' . $username . ' ' . TV_EPG_SAVED . DIRECTORY_SEPARATOR . $id . '.xml' . PHP_EOL;
            $script .= 'fi' . PHP_EOL;

            $this->_debug("Run script EPG for " . $id . '.xml');
            $this->_debug($script);

            // Save the bash script
            $bash = TV_EPG . DIRECTORY_SEPARATOR . '.unlock';

            // Avoid to have an old file
            if (file_exists($bash))
                unlink($bash);

            // Put the content in 
            $fp = fopen($bash, 'w+');
            flock($fp, LOCK_EX);
            fwrite($fp, $script);
            fclose($fp);

            // Turn into executable
            chmod($bash, 0755);

            // Execute the bash                
            exec("sudo $bash", $output);
            foreach ($output as $line) $this->_debug("\t$line");

            // Remove the script for security reasons
            if (file_exists($bash))
                unlink($bash);

            // Return result
            return true;
        }
        return false;
    }

    public function Lock()
    {
        if (!$this->islocked) {
            $this->_debug("Lock EPG");
            if (file_exists($this->lockfile))
                unlink($this->lockfile);

            file_put_contents($this->lockfile, time());
            $this->islocked = true;
        }
    }

    public function Unlock($key)
    {
        // Unlock if there is no key yet
        if (!file_exists($this->lockfile) || !isset($_SESSION['cronjob'])) {
            $this->_debug("Unlock EPG");
            $this->islocked = false;
            return true;
        }

        // Unlock if the key is expired        
        if ($this->readLock() < time() - (int) $this->params['lock']) {
            if (file_exists($this->lockfile)) unlink($this->lockfile);
            unset($_SESSION['cronjob']);
            $this->_debug("Unlock EPG");
            $this->islocked = false;
            return true;
        }

        // Existing lock not expired, then decrypt the key
        $cron_id = $this->_Decrypt($key);

        // Check the key
        if (isset($_SESSION['cronjob']) && $cron_id !== $_SESSION['cronjob'])
            $this->_debug("Unlock EPG failed!");
        $this->islocked = true;
        return false;

        // When unlocked, ensure the cron_id and the file are both destroyed
        if (file_exists($this->lockfile)) unlink($this->lockfile);
        unset($_SESSION['cronjob']);

        // Return success
        $this->_debug("Unlock EPG");
        return true;
    }

    public function readLock()
    {
        if (!file_exists($this->lockfile)) return 0;
        return (int) file_get_contents($this->lockfile);
    }

    public function getCronId()
    {
        if (!isset($_SESSION['cronjob']))
            $_SESSION['cronjob'] = $this->_generateCronId();

        return $this->_Encrypt($_SESSION['cronjob']);
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

    protected function _debug($message)
    {
        if ($this->params['debug'] && strlen($message)) {
            //echo "$message\n";
            error_log($message);
        }
    }
}
