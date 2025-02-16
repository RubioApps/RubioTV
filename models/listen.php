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
use RubioTV\Framework\Model;
use RubioTV\Framework\M3U;
use RubioTV\Framework\EPG;
use RubioTV\Framework\Language\Text;

class modelListen extends Model
{
    protected $id;
    protected $data;
    protected $item;
    protected $buffered;
    protected $startTime;
    protected $targetDuration;
    protected $headers;
    protected $info;
    protected $recursive;
    protected $record;
    protected $time;
    protected $debug;

    public function display($id = null)
    {
        if (!$id && $this->params->id)
            $id = $this->params->id;

        $this->id = $id;
        $this->record = false;

        if (!$this->id) {
            $this->page->setFile('404.php');
            $this->page->sendError(Text::_('ERROR'), Text::_('ERROR_VIEW'));
            return false;
        }

        // Remove the existing recordings for this session
        $config = Factory::getConfig();
        $sid    = md5(session_id() . $config->password);
        $target = TV_RADIO . DIRECTORY_SEPARATOR . 'recordings' . DIRECTORY_SEPARATOR . $sid . '.mp3';
        if (file_exists($target)) unlink($target);

        // Get the SEF
        $this->params->source_alias = $this->params->source_alias ?? SEF::rfind($this->params->folder, $this->params->source);

        // Set some token for the page. Then, if any error, we will have the breadcumnb
        $this->page->folder         = $this->params->folder;
        $this->page->source         = $this->params->source;
        $this->page->source_alias   = $this->params->source_alias;

        if (!$this->_data()) {
            $this->page->setFile('404.php');
            $this->page->sendError(Text::_('ERROR'), Text::_('ERROR_SOURCE'));
            header('Refresh: 3;url=' . Factory::Link('radio', $this->params->folder, $this->params->source . ':' . $this->params->source_alias));
            return false;
        }

        if (!isset($this->data[$this->id])) {
            $this->page->setFile('404.php');
            $this->page->sendError(Text::_('ERROR'), Text::_('ERROR_VIEW'));
            header('Refresh: 3;url=' . Factory::Link('radio', $this->params->folder, $this->params->source . ':' . $this->params->source_alias));
            return false;
        }

        // Set the page
        $this->page->title          = htmlentities($this->item->name);
        $this->page->alias          = $this->item->name;
        $this->page->link           = $this->item->url;

        // Set the reload link
        $this->item->sync = Factory::Link('listen.sync', $this->params->folder, $this->params->source, $this->id);

        // Metatags        
        $this->page->addMeta('description', $this->page->title);
        $this->page->addMeta('keywords', Text::_($this->params->folder));
        $this->page->addMeta('keywords', Text::_($this->params->source));
        $this->page->addMeta('keywords', Text::_($this->params->source_alias));

        // Set the item into the page
        $this->page->data = $this->item;

        // Must allow CORS
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day        

        parent::display();
    }

    public function sync()
    {

        $ping       = Request::getInt('ping', time(), 'GET');
        $record     = Request::getVar('record', 'false', 'GET');

        if (!$this->id && $this->params->id)
            $id = $this->params->id;

        $this->id   = $id;
        $this->recursive = 0;
        $this->debug = false;
        $this->buffered = false;
        $this->record = ($record == 'true');

        $result = [
            'success'   => false,
            'name'     => '',
            'title'     => '',
            'logo'      => '',
            'buffered'  => false,
            'info'      => null,
            'icy'       => null,
            'pong'      => 0,
            'lapse'     => 0,
            'sources'   => null
        ];

        if ($this->_data() && isset($this->data[$this->id])) {
            $result['name'] = $this->item->name;
            $result['logo']  = $this->item->image;
            $result['title'] = $this->_getTitle();
            $result['icy'] = $this->_getIcy();
            $result['sources']  = $this->item->url;

            //If the audio file is m3u or m3u8, process the file to get the links
            if ($list = $this->_convert()) {
                $result['success']  = true;
                $result['pong']     = time() * 1000;            

                if ($this->buffered) {
                    $result['info']     = $this->info;
                    $result['buffered'] = true;
                    $result['lapse']    = floatval(($this->time - $ping) / 1000);
                    $result['sources']  = $list;
                }
            }
        }

        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
        exit(0);
    }

    public function refresh()
    {
        if (!$this->id && $this->params->id)
            $id = $this->params->id;

        $this->id   = $id;

        $result = [
            'success'   => false,
            'title'     => '',
            'icy'       => null,
        ];

        if ($this->_data() && isset($this->data[$this->id])) {
            $result['success']  = true;
            $result['title'] = $this->_getTitle();
            $result['icy'] = $this->_getIcy();
        }

        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
        exit(0);
    }

    public function record()
    {
        $config = Factory::getConfig();
        $do     = Request::getVar('do', 'start', 'GET');
        $fileid = md5(session_id() . $config->password);
        $mp3 = TV_RADIO . DIRECTORY_SEPARATOR . 'recordings' . DIRECTORY_SEPARATOR . $fileid . '.mp3';
        ob_end_clean();
        switch ($do) {
            case 'download':
                header('Content-type: audio/mpeg');
                header('Content-Disposition: attachment; filename="' . $fileid . '"');
                header('Content-length: ' . filesize($mp3));
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                readfile($mp3);
                break;
            case 'start':
            default:
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true]);
        }
        if (file_exists($mp3)) unlink($mp3);
        exit(0);
    }

    protected function _convert()
    {
        if (!$this->item->url || !$this->item->id || !preg_match('/^http(s):\/\/(.*\.(m3u[8]?)(\??.*))$/i', $this->item->url)) { 
            $this->buffered = false;
            return true;  
        }

        // Initialize
        $path       = sys_get_temp_dir();
        $list       = false;
        $ping       = Request::getInt('ping', 1000 * time(), 'GET');

        // Get the source m3u
        $content = file_get_contents($this->item->url);
        $this->log('Convert ' . $this->item->url);
        if ($array = $this->_parse($content)) {             
            // Purge the files created older than 5 min (max. buffer length)
            if ($dir = opendir(TV_RADIO . DIRECTORY_SEPARATOR . 'buffer')) {
                while (($f = readdir($dir)) !== false) {
                    if ($f != '.' && $f != '..') {
                        $pong = 1000 * filemtime(TV_RADIO . DIRECTORY_SEPARATOR . 'buffer' . DIRECTORY_SEPARATOR . $f);
                        if ($ping - $pong > 300 * 1000) {
                            $this->log('Purge ' . $f);
                            unlink(TV_RADIO . DIRECTORY_SEPARATOR . 'buffer' . DIRECTORY_SEPARATOR . $f);
                        }
                    }
                }
                closedir($dir);
            }

            // Set the current time in ms
            $this->time = time() * 1000;
            $list = [];

            foreach ($array as $item) {

                // File ID
                $id = md5(pathinfo($item->url, PATHINFO_FILENAME));
                $target = TV_RADIO . DIRECTORY_SEPARATOR . 'buffer' . DIRECTORY_SEPARATOR . $id . '.mp3';
                if (!file_exists($target)) {

                    // Retrieve the remote file
                    $this->log('Retrieve ' . $item->url);
                    $ch = curl_init();;
                    curl_setopt($ch, CURLOPT_URL, $item->url);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    $content    = curl_exec($ch);
                    $code       = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
                    curl_close($ch);
                    $error = curl_errno($ch);

                    $this->log('Response ' . $code);

                    // If no error, save it locally and convert it to mp3
                    if ($error === 0  && ($code == 200 || $code == 301 || $code == 302)) {
                        $ext = pathinfo($item->url, PATHINFO_EXTENSION);
                        $filename = $path . DIRECTORY_SEPARATOR . $id . $ext;
                        $fp = fopen($filename, 'wb');
                        fwrite($fp, $content);
                        fclose($fp);

                        // Convert to mp3                                               
                        if ($error = exec('ffmpeg -i ' . $filename . ' ' . $target . ' 2>&1' . PHP_EOL)) {
                            $list[] = $target;
                        }

                        // Remote local version of the remote file
                        unlink($filename);

                        //If record is enable, merge the list into a unique file
                        if ($this->record) $this->_record($target);
                    }
                }

                // Increase the timer  
                $this->time += $item->duration;
            }

            //Return the list
            $this->log('List of results:');
            $this->log($list);
            if (count($list)) {
                array_walk($list, function (&$value, $key) {
                    $config = Factory::getConfig();
                    $filename = pathinfo($value, PATHINFO_BASENAME);
                    $value = $config->live_site . '/cache/radio/buffer/' .  $filename;
                });
                $this->recursive = 0;
                $this->buffered = true;
                return $list;
            } else {
                $this->recursive++;
                $this->log('Recursive conversion calls:' . $this->recursive);
                return $this->recursive < 3 ? $this->_convert() : false;
            }
        }
        return false;
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

        return true;
    }

    protected function _parse($text)
    {
        if ($this->recursive > 3) return false;

        $ping  = Request::getInt('ping', time(), 'GET');
        $match = [];
        $this->targetDuration = $this->_duration($text);
        $this->startTime = $this->_startTime($text);

        //EXTINF found
        if (preg_match_all('/#EXTINF:(.*)\n(.*)/im', $text, $match)) {
            $array = [];
            for ($i = 0, $counter = $this->startTime; $i < count($match[2]); $i++) {
                $item = new \stdClass;
                $item->url      = $this->_completeURI($match[2][$i]);
                $item->duration = round(1000 * floatval($match[1][$i]));

                //Only push the tracks before the ping (ensure 5 tracks at least)
                if ($counter < $ping - 5 * $this->targetDuration) {
                    $counter += $item->duration;
                    continue;
                }
                $array[] = $item;
                $this->log('EXTINF found: ' . print_r($array, true));
            }
            return $array;
        }    
        //EXTINF not found, check M3U8
        else {
            //Iteration with another m3u ?
            if (preg_match_all('/(.*\.(m3u[8]?)(\??.*))$/im', $text, $match)) {

                $this->buffered = true;
                $this->info     = $this->_info($text);

                $last = count($match[0]) - 1;
                $string = $match[0][$last];

                //Detect the path and the query string
                //This is used in M3U8 token format used for Akamai
                if (strstr($string, '/') !== false) {
                    $path = strstr($string, '/');
                    $query = strstr($string, '/', true);
                } else {
                    $path = $string;
                    $query = null;
                }
                $item = $this->_completeURI($path . ($query ? '?' . $query : ''));
                $this->log('Parse M3U: ' . $item);
                if ($content = file_get_contents($item)) {
                    $this->recursive++;
                    return $this->_parse($content);
                }
            }
        }
        return false;
    }

    protected function _duration($text)
    {
        $result = 0;
        $match = [];
        if (preg_match_all('/#EXT-X-TARGETDURATION:(.*)$/im', $text, $match)) {
            $this->log('Target duration: ' . print_r($match, true));
            if (isset($match[1])) {
                if (!empty($match[1][0])) $result = intval(trim(($match[1][0]))) ?: 0;
            }
        }
        $this->log('Duration: ' . print_r($result, true));
        return 1000 * $result;
    }
    //#EXT-X-DISCONTINUITY-SEQUENCE:0

    protected function _startTime($text)
    {
        $result = 0;
        $match  = [];
        if (preg_match_all('/#EXT-X-MEDIA-SEQUENCE:(.*)$/im', $text, $match)) {
            if (isset($match[1])) {
                if (!empty($match[1][0])) intval(trim(($match[1][0]))) ?: 0;
            }
        }
        //Put the programe date after the media sequence because media sequence is not forcely timestamp
        if (preg_match_all('/#EXT-X-PROGRAM-DATE-TIME:(.*)$/im', $text, $match)) {
            if (isset($match[1])) {
                if (!empty($match[1][0])) $result = strtotime(trim(($match[1][0]))) ?: 0;
            }
        }
        $this->log('StartTime: ' . print_r($result, true));
        return 1000 * ($result ?: time());
    }

    protected function _info($text)
    {
        $result = null;
        $match = [];
        if (preg_match_all('/#EXT-X-STREAM-INF:(.*)$/im', $text, $match)) {
            if (isset($match[1])) {
                if (!empty($match[1][0])) {
                    if (strstr($match[1][0], ',')) {
                        $array = explode(',', $match[1][0]);
                    } else {
                        $array = array($match[1][0]);
                    }
                    $result = [];
                    foreach ($array as $prop) {
                        $p = explode('=', $prop);
                        $p[1] = preg_replace('/[\"]+/', '', trim($p[1]));
                        $result[$p[0]] = $p[1];
                    }
                    $this->log('Info: ' . print_r($result, true));
                }
            }
        }
        return $result ?: null;
    }

    protected function _completeURI($file)
    {
        $prefix = '';
        if (substr($file, 0, 4) !== 'http') {
            $http = parse_url($this->item->url, PHP_URL_SCHEME);
            $host = parse_url($this->item->url, PHP_URL_HOST);
            $path = parse_url($this->item->url, PHP_URL_PATH);
            $array = explode('/', $path);
            array_pop($array);
            $prefix = $http . '://' . $host . join('/', $array);
            if (substr($file, -1) !== '/') $prefix .= '/';
        }
        return $prefix . $file;
    }

    protected function _record($file)
    {
        if (!$this->record) return;

        $config = Factory::getConfig();
        $sid    = md5(session_id() . $config->password);
        $target = TV_RADIO . DIRECTORY_SEPARATOR . 'recordings' . DIRECTORY_SEPARATOR . $sid . '.mp3';
        $temp   = TV_RADIO . DIRECTORY_SEPARATOR . 'recordings' . DIRECTORY_SEPARATOR . '_' . $sid . '.mp3';

        $array  = [];

        if (file_exists($target)) $array[] = $target;
        $array[] = $file;

        $this->log('Merged MP3: ' . print_r($array, true));
        $command = 'ffmpeg -i "concat:' . join('|', $array) . '" -acodec copy ' . $temp . ' 2>&1' . PHP_EOL;
        $command .= 'mv ' . $temp . ' ' . $target . PHP_EOL;
        return exec($command);
    }

    protected function _checkIcecast()
    {
        if (!$this->headers) {
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => 'Icy-MetaData: 1'
                ]
            ];          
            $context = stream_context_create($opts);
            $this->headers = get_headers($this->item->url, true,$context);
        }

        if (strpos($this->headers[0], '200 OK') !== false && isset($this->headers['ice-audio-info'])) {
            $array = explode(';', $this->headers['ice-audio-info']);
            $this->info = [];
            foreach ($array as $prop) {
                $pair = explode('=', $prop);
                $key = strtolower($pair[0]);
                $value = $pair[1];
                if (substr($key, 0, 4) == 'ice-') $key = substr($key, 4);
                $this->info[strtoupper($key)] = $value;
            }
            return true;
        };
        return false;
    }

    protected function _getIcy()
    {
        if (!$this->_checkIcecast($this->item->url)) return null;
        $icy = [
            'icy-description',
            'icy-genre',
            'icy-name',
            'icy-url',
            'icy-metaint',
        ];

        if (strpos($this->headers[0], '200 OK') !== false) {
            $result = [];
            foreach ($this->headers as $key => $value) {
                if (in_array($key, $icy)) {
                    $result[strtoupper(substr($key, 4))] = $value;
                }
            }
            return count($result) ? $result : false;
        };
        return false;
    }

    protected function _getTitle($length = 19200,  $offset = 0)
    {        
        if (!$this->_checkIcecast($this->item->url)) return null;

        if(!$this->headers || !isset($this->headers['icy-metaint'])) return null;

        // Limit to 2 cycles
        if ($offset > 2 * 19200) return null;

        $needle = 'StreamTitle=';
        $ua = 'Mozilla/5.0 AppleWebKit/537.36 Chrome/100.0.4896.81 Safari/537.36 Vivaldi/5.2.2623.26';
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => 'Icy-MetaData: 1',
                'user_agent' => $ua
            ]
        ];

        //Locate the length
        if ($this->headers) {
            foreach ($this->headers as $key => $value) {
                if (strpos(strtolower($key), 'icy-metaint') !== false) {
                    $length = $value;
                    break;
                }
            }
        }      
       
        try {
            $context = stream_context_create($opts);
            if ($stream = fopen($this->item->url, 'r', false, $context)) {

                //Get a chunk
                $buffer = stream_get_contents($stream, $length, $offset);
                fclose($stream);

                // Look for the needle
                if (strpos($buffer, $needle) !== false) {
                    $title = explode($needle, $buffer)[1];
                    return substr($title, 1, strpos($title, ';') - 2);
                } else {
                    return $this->_getTitle($length, $offset + $length);
                }
            } else {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function _link()
    {
        return $this->link;
    }

    protected function log($message, $level = 0)
    {
        if (!$this->debug) return;
        error_log(print_r($message, true), $level);
    }
}
