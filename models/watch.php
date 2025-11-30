<?php

/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.6.1                                                           |
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
use RubioTV\Framework\Model;
use RubioTV\Framework\M3U;
use RubioTV\Framework\EPG;
use RubioTV\Framework\Language\Text;

class modelWatch extends Model
{
    protected $id;
    protected $data;
    protected $item;
    protected $epg;
    protected $streams;
    protected $guides;

    public function display($id = null)
    {
        if (!$id && $this->params->id)
            $id = $this->params->id;

        $this->id = $id;

        if (!$this->id) {
            $this->page->setFile('404.php');
            $this->page->sendError(Text::_('ERROR'), Text::_('ERROR_VIEW'));
            return false;
        }

        // Get the SEF
        $this->params->source_alias = $this->params->source_alias ?? SEF::rfind($this->params->folder, $this->params->source);

        // Set some token for the page. Then, if any error, we will have the breadcumnb
        $this->page->folder         = $this->params->folder;
        $this->page->source         = $this->params->source;
        $this->page->source_alias   = $this->params->source_alias;

        if (!$this->_data()) {
            $this->page->setFile('404.php');
            $this->page->sendError(Text::_('ERROR'), Text::_('ERROR_SOURCE'));
            header('Refresh: 3;url=' . Factory::Link('channels', $this->params->folder, $this->params->source . ':' . $this->params->source_alias));
            return false;
        }

        if (!isset($this->data[$this->id])) {
            $this->page->setFile('404.php');
            $this->page->sendError(Text::_('ERROR'), Text::_('ERROR_VIEW'));
            header('Refresh: 3;url=' . Factory::Link('channels', $this->params->folder, $this->params->source . ':' . $this->params->source_alias));
            return false;
        }

        // Set the page
        $this->page->title          = htmlentities($this->item->name);
        $this->page->alias          = $this->item->name;
        $this->page->link           = $this->item->url;
        $this->page->saved          = $this->_find('custom', 'playlist', $this->id);

        // Metatags        
        $this->page->addMeta('description', $this->page->title);
        $this->page->addMeta('keywords', Text::_($this->params->folder));
        $this->page->addMeta('keywords', Text::_($this->params->source));
        $this->page->addMeta('keywords', Text::_($this->params->source_alias));

        // Is the guide available?
        if ($this->item->playing) {
            $this->page->addMeta('keywords', $this->item->playing->title);
            $this->page->addMeta('description', $this->item->playing->title, ' ');
            $this->page->addMeta('description', $this->item->playing->subtitle, ' ');
            $this->page->addMeta('description', $this->item->playing->desc, ' ');
        }

        // Set the item into the page
        $this->page->data = $this->item;

        //Notify EPG pending or missing
        if ((bool) $this->config->epg['notify']) {
            if ($this->item->xmltv === true)
                $this->page->sendSuccess(Text::_('GUIDES'), 'Pending XMLTV for ' . $this->item->tvg_id);
            elseif ($this->item->xmltv === false)
                $this->page->sendMessage(Text::_('GUIDES'), 'Missing XMLTV from ' . $this->item->tvg_id, 'info');
        }

        parent::display();
    }

    public function heartbeat()
    {
        $result = ['result' => 'error' , 'id' => null];
        if ($id = Request::getVar('id',null,'GET') )
        {
            $result = ['result' => 'error','id' => $id];
            $dir        = TV_CACHE_HLS . DIRECTORY_SEPARATOR . $id;
            $file       = $dir . DIRECTORY_SEPARATOR . 'lastview';
            if(file_exists($file)) @unlink($file);            
            if(file_put_contents($file , time()) !== false) $result = ['result' => 'success','id' => $id];
        }
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit(0);
    }

    public function cron($id = null)
    {
        $result = [
            'success'   => false,
            'title'     => Text::_('ERROR'),
            'content'   => Text::_('CRON_ERROR')
        ];

        if (!$id && $this->params->id)
            $id = $this->params->id;

        $this->id = $id;

        if ($this->id && $this->params->format === 'json' && isset($_POST['key'])) {
            $this->epg    = new EPG();
            if ($this->epg->Unlock($_POST['key'])) {
                if ($this->epg->Process($this->id)) {
                    $result['success'] = true;
                    $result['title'] = Text::_('SUCCESS');
                    $result['content'] = Text::_('CRON_SUCCESS');
                }
                $this->epg->Lock();
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
        exit(0);
    }

    protected function _data()
    {
        // Get the channels
        $m3u = new M3U($this->params->folder, $this->params->source);
        $this->data = $m3u->load();
        $this->item = $this->data[$this->id];

        // Check the url of the channel is not empty
        if (!isset($this->item->url))
            return false;

        // Does the URL channel exist?
        if (!$m3u->isvalid($this->item->id) || !$this->item->url = filter_var($this->item->url, FILTER_VALIDATE_URL))
            return false;   

        // Setup mime type
        $this->item->mime = $this->config->dtv['mime'];

        // For DTV, take the fixed url by the config
        if (strstr($this->item->url, $this->config->dtv['host']) !== false) {
            $this->item->dtv = true;
            // Set the EPG
            $this->item->xmltv  = $this->config->dtv['host'] . $this->config->dtv['xmltv'];
            // Override the current URL by the playlist of encoded stream
            if($this->config->dtv['encode'] && !$this->_is_mobile()){
                $this->_encode($this->item);            
                $this->item->url = $this->config->live_site . '/cache/hls/' . $this->item->id . '/playlist.m3u8';
                $this->item->mime = 'application/x-mpegURL';
                $this->item->heartbeat = Factory::Link('watch.heartbeat', $this->params->folder, $this->params->source, $this->item->id);
            }

            // For IPTV sources (non DTV)            
        } else {
            // Get a temporary key to unlock the cron for EPG
            $this->item->dtv        = false;
            $this->item->mime       = 'application/x-mpegURL';
            $this->epg              = new EPG();
            $this->item->epg_key    = $this->epg->getCronId();
            $this->item->xmltv      = $this->epg->getXMLTV($this->item->id, $this->item->tvg_id);
            $this->_stop_ffmpeg();
        }

        if ($this->item->xmltv !== false && filter_var($this->item->xmltv, FILTER_VALIDATE_URL)) {
            $this->epg  = new EPG($this->item->xmltv);
            list($this->item->guide, $this->item->playing) = $this->epg->getGuide($this->item->tvg_id);
        } else {
            list($this->item->guide, $this->item->playing) = array(null, null);
        }

        return true;
    }

    /**
     * Encode a live stream from DTV to make it visible through any web browser
     */
    protected function _encode($item)
    {
        // Set files
        $dir        = TV_CACHE_HLS . DIRECTORY_SEPARATOR . $item->id;
        $segment    = $dir . DIRECTORY_SEPARATOR . '%04d.ts';
        $playlist   = $dir . DIRECTORY_SEPARATOR . 'playlist.m3u8';

        // First launch: the playlist does not exist yet
        if (!file_exists($playlist)) {

            // Due to the restrictions, we only can have 1 live DTV streaming at once
            $this->_stop_ffmpeg();

            // Re-create the HLS directory for the current view
            if (!file_exists(TV_CACHE_HLS)) {
                mkdir(TV_CACHE_HLS, 0755, true);
            }
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            // Prepare the ffmpeg command
            $cmd = [
                "ffmpeg",
                "-i " . escapeshellarg($item->url),
                //Video
                "-c:v libx264",
                "-preset veryfast",
                //Audio
                "-c:a aac",
                "-b:a 128k",
                //HLS
                "-hls_time 2",
                "-hls_list_size 15",
                "-hls_flags delete_segments+temp_file",
                "-hls_segment_filename $segment",
                "-f hls",
                $playlist,
            ];

            $descriptors = [
                0 => ["pipe", "r"],   // stdin
                1 => ["pipe", "w"],   // stdout
                2 => ["pipe", "w"]    // stderr
            ];

            $proc   = proc_open(implode(' ', $cmd), $descriptors, $pipes, null, null, ['bypass_shell' => true]);

            // Store the PID
            $status = proc_get_status($proc);
            $pid    = (int)$status['pid'] + 1;
            file_put_contents($dir . DIRECTORY_SEPARATOR . 'pid', $pid);

            if (is_resource($proc)) {
                fclose($pipes[0]);
            }

            // Wait up to 5s to create the playlist
            $wait = 0;
            while (!file_exists($playlist) && $wait < 50) {
                usleep(100000);
                $wait++;
            }
        }
        //Set first view
        if(file_exists($dir . DIRECTORY_SEPARATOR . 'lastview')) @unlink($dir . DIRECTORY_SEPARATOR . 'lastview');
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'lastview', time());            
    }

    protected function _streams()
    {
        if (!$this->page->data)
            return false;

        $this->streams = IPTV::getStreams();

        $this->page->data->streams = [];
        $this->page->data->timeshift = 0;

        foreach ($this->streams as $s) {
            if ($this->page->data->url === $s->url) {
                $this->page->data->timeshift = $s->timeshift;
                $this->page->data->streams[] = $s->url;
                break;
            }
        }
    }

    protected function _guides()
    {
        if (!$this->page->data)
            return false;

        $this->guides = IPTV::getGuides();
        $this->page->data->guides = [];

        foreach ($this->guides as $g) {
            if ($this->page->data->tvg_id === $g->channel) {
                $this->page->data->guides[] = $g->site_id;
                break;
            }
        }
    }

    protected function _link()
    {
        return $this->link;
    }

    protected function _is_mobile() {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
        $agents = ['mobile', 'android', 'iphone', 'ipad', 'ipod', 'blackberry', 'windows phone'];        
    
        foreach ($agents as $a) {
            if (strstr($ua, $a) !== false) {
                return true;
            }
        }
        return false;
    }
  
    protected function _stop_ffmpeg()
    {
        $files = glob(TV_CACHE_HLS . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'pid');
        if (count($files)) {
            foreach ($files as $file) {
                $pid = @file_get_contents($file);
                exec("kill $pid 2>/dev/null");
            }
            // Empty HLS directory
            return Factory::RemoveDir(TV_CACHE_HLS);
        }
    }
}
