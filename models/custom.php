<?php

/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.5.1                                                           |
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
use RubioTV\Framework\M3U;
use RubioTV\Framework\Language\Text;

class modelCustom extends Model
{
    protected $m3u;
    protected $error;
    protected $message;
    protected $files;

    /**
     * Display the list of custom lists
     */
    public function display()
    {
        $this->files = $this->_folders(TV_IPTV . DIRECTORY_SEPARATOR . 'custom');

        $ini  = TV_IPTV . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . 'custom.ini';
        // Check if there is a _custom.ini within the folder $root
        if (!file_exists($ini)) {
            $content  = '[system]' .  PHP_EOL;
            $content .= 'playlist = playlist' .  PHP_EOL;
            $content .= '[custom]' .  PHP_EOL;
            file_put_contents($ini, $content);
            $array = false;
        }

        $list   = [];
        $array  = parse_ini_file($ini, true);
        foreach ($array as $acl => $section) {
            foreach ($section as $key => $value) {
                if (isset($this->files[$key])) {
                    $item = $this->files[$key];
                    $item->type = $acl;
                    if ($acl === 'system') {
                        $item->label = Text::_($value);
                        $item->link  = Factory::Link('channels', 'custom', $item->name);
                    } else {
                        $item->label = $value;
                        $item->link  = Factory::Link('channels', 'custom', $item->name . ':' . $item->label);
                        $item->image = Factory::getAssets() . '/images/imported.png';
                    }
                    $list[] = $item;
                }
            }
        }

        $this->page->title  = Text::_('CUSTOM');
        $this->page->data = $list;

        parent::display();
    }

    /**
     * Add a new custom list
     */
    public function new()
    {
        $this->error    = null;
        $this->message  = 'Invalid token';
        $post           = Request::get('POST');

        if (!Factory::checkToken())
            $this->error = ERR_INVALID_TOKEN;

        if (!$this->error && isset($post['listname'])) {
            $ini = TV_IPTV . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . 'custom.ini';

            // Sanity check
            if (! $listname = filter_input(INPUT_POST, 'listname', FILTER_DEFAULT))
                $this->error = ERR_INVALID_LISTNAME;

            // Length check            
            $listname = trim($listname);
            if (strlen(trim($listname)) < 2 || strlen(trim($listname)) > 12)
                $this->error = ERR_INVALID_LISTNAME;

            if (!$this->error) {
                // Set the filename (hash)                                    
                $filename   = strtolower(preg_replace('/[\W]/', '', $listname));

                // Ensure a unique name
                $array      = parse_ini_file($ini, false);
                if (!$array  || in_array($filename, $array))
                    $this->error = ERR_INVALID_LISTNAME;
            }

            // Parse the ini and check the max.
            if (!$this->error) {
                $array      = parse_ini_file($ini, true);
                $array['custom'][$filename] = $listname;

                if (count($array['custom']) > 10)
                    $this->error = ERR_MAX_LISTS_REACHED;
            }

            // Save the filename in the custom.ini
            if (!$this->error) {
                if (!$this->_save_ini($array, $ini))
                    $this->error = ERR_INVALID_LISTNAME;
            }

            // Error while writing the ini file
            if (!$this->error) {
                $m3u = new M3U('custom', $filename);
                $m3u->load();
            }
        } else {
            $this->error = ERR_INVALID_LISTNAME;
        }

        header('Refresh:1 ; url=' . Factory::Link('custom'));
        $this->_errCheck();
        $this->display();
    }

    /**
     * Erase a customized list of channels
     */
    public function erase()
    {
        $this->error    = null;
        $this->message  = Text::_('ERROR_ERASE_LIST');
        $post           = Request::get('POST');

        if (!Factory::checkToken())
            $this->error = ERR_INVALID_TOKEN;

        if (!$this->error && isset($post['id'])) {
            $ini    = TV_IPTV . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . 'custom.ini';
            $array  = parse_ini_file($ini, true);
            $id     = $post['id'];

            if (!isset($array['custom'][$id])) {
                $this->error = ERR_INVALID_LISTNAME;
            } else {
                unset($array['custom'][$id]);
                unlink(TV_IPTV . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . $id . '.m3u');
            }
        }

        if (!$this->error) {
            $this->_save_ini($array, $ini);
            $this->message = Text::_('SUCCESS_ERASE_LIST');
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $this->error, 'message' => $this->message]);
        exit(0);
    }

    /**
     * Edit a custom list, and allow to sort and delete
     */

    public function edit()
    {
        $this->error    = null;
        $this->message  = 'Invalid token';
        $this->data     = $this->_data();
        $this->link     = $this->_link();
        $post           = Request::get('POST');
        $format         = Request::getVar('format', '', 'GET');

        if (!Factory::checkToken())
            $this->error = ERR_INVALID_TOKEN;

        if (!$this->error && isset($post['ids']) && $format === 'json') {
            $this->error = $this->m3u->remove($post['ids']);
            $this->message = 'Edit item';

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => !$this->error , 'message' => $this->message]);
            exit(0);
        }

        $this->page->title      = Text::_($this->params->source);
        $this->page->folder     = $this->params->folder;
        $this->page->source     = $this->params->source;
        $this->page->alias      = $this->params->source_alias ?? SEF::rfind($this->params->folder, $this->params->source);
        $this->page->pagination = $this->_pagination();
        $this->page->data       = $this->data;
        $this->page->link       = $this->link;
        $this->page->setFile('edit.php');

        parent::display();
    }

    /**
     * Remove a channel from a custom list
     */
    public function remove()
    {
        $this->error    = null;
        $this->message  = 'Invalid token';
        $post           = Request::get('POST');

        if (!Factory::checkToken())
            $this->error = ERR_INVALID_TOKEN;

        if (!$this->error && isset($post['ids'])) {
            $this->data = $this->_data();
            $this->error = $this->m3u->remove($post['ids']);
            $this->message = 'Remove items';
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => !$this->error , 'message' => $this->message]);
        exit(0);        
    }

    /**
     * Sort the list of channels within a custom list
     */
    public function sort()
    {
        $this->error    = null;
        $this->message  = 'Invalid token';
        $post           = Request::get('POST');

        if (!Factory::checkToken())
            $this->error = ERR_INVALID_TOKEN;

        if (!$this->error && isset($post['ids'])) {
            $this->data = $this->_data();
            $this->error = $this->m3u->sort($post['ids']);
            $this->message = 'Sort items';
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => !$this->error , 'message' => $this->message]);
        exit(0);           
    }

    /**
     * Add a single channel to a custom list
     */
    public function add()
    {
        $input          = new \stdClass();
        $input->group   = 'Imported';
        $this->error    = null;
        $post           = Request::get('POST');

        foreach ($post as $k => $v) {
            $v = trim($v);
            if ($v !== null && strlen($v))
                $input->$k = $v;
        }

        if (empty($input->target) || (empty($input->target)))
            $this->error = ERR_MISSING_LISTNAME;

        if (empty($input->name) || (empty($input->url)))
            $this->error = ERR_IMPORT_EMPTY_FIELD;

        if (!filter_var($input->url, FILTER_VALIDATE_URL))
            $this->error = ERR_IMPORT_INVALID_URL;

        if (empty($input->id))
            $input->tvg_id = $input->id = md5($input->name);
        else
            $input->tvg_id = $input->id;

        if (empty($input->logo))
            $input->logo = Factory::getAssets() . '/images/notfound.png';
        else
            if (!filter_var($input->logo, FILTER_VALIDATE_URL))
            $this->error = ERR_IMPORT_INVALID_URL;

        if (!$this->error) {
            $this->m3u  = new M3U('custom', $input->target);
            if (!$this->m3u->append($input))
                $this->error = ERR_IMPORT_ANY;
            else
                $this->error = ERR_IMPORT_NONE;
        }

        $this->_errCheck();
        $this->display();
    }

    /**
     * Import a plain text to a custom list
     */
    public function brut()
    {
        $input          = new \stdClass();
        $input->group   = 'Imported';
        $this->error    = null;
        $post           = Request::get('POST');

        foreach ($post as $k => $v) {
            $v = trim($v);
            if ($v !== null && strlen($v))
                $input->$k = $v;
        }

        if (empty($input->target) || (empty($input->target)))
            $this->error = ERR_MISSING_LISTNAME;

        if (empty($input->brut))
            $this->error = ERR_IMPORT_ANY;

        if (!$this->error) {
            $this->m3u      = new M3U('custom', $input->target);
            $this->m3u->import(trim($input->brut));
        }

        $this->_errCheck();
        $this->display();
    }

    /**
     * Upload a file into a custom list
     */
    public function upload()
    {
        $input          = new \stdClass();
        $input->group   = 'Imported';
        $this->error    = null;
        $post           = Request::get('POST');

        foreach ($post as $k => $v) {
            $v = trim($v);
            if ($v !== null && strlen($v))
                $input->$k = $v;
        }

        if (empty($input->target) || (empty($input->target)))
            $this->error = ERR_MISSING_LISTNAME;

        if (
            empty($_FILES['file']) ||
            $_FILES['file']['type'] !== 'audio/x-mpegurl' ||
            $_FILES['file']['size'] > 4096 * 1024 ||
            $_FILES['file']['error'] ||
            !is_uploaded_file($_FILES['file']['tmp_name'])
        )
            $this->error = ERR_IMPORT_INVALID_FILE;

        if (!$this->error) {
            $input->brut = file_get_contents($_FILES['file']['tmp_name']);
            $this->m3u   = new M3U('custom', $input->target);
            $this->m3u->import(trim($input->brut));
        }

        $this->_errCheck();
        $this->display();
    }

    /**
     * Search a channel within a custom list
     */
    public function search()
    {
        $format     = Request::getVar('format', '', 'GET');
        $this->data = $this->_data();

        if ($format === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            echo $this->_term();
            exit(0);
        }
    }

    protected function _data()
    {
        $this->m3u = new M3U($this->params->folder, $this->params->source);
        $this->data = $this->m3u->load();
        return $this->data;
    }

    protected function _errCheck()
    {
        $format         = Request::getVar('format', '', 'GET');

        switch ($this->error) {
            case ERR_INVALID_TOKEN:
                $this->message  = 'Invalid token';
                break;
            case ERR_IMPORT_EMPTY_FIELD:
                $this->message = Text::_('IMPORT_EMPTY_FIELD');
                break;

            case ERR_IMPORT_INVALID_URL:
                $this->message = Text::_('IMPORT_INVALID_URL');
                break;

            case ERR_IMPORT_ANY:
                $this->message = Text::_('IMPORT_ERROR');
                break;

            case ERR_IMPORT_INVALID_FILE:
                $this->message = Text::_('IMPORT_INVALID_FILE');
                break;

            case ERR_INVALID_LISTNAME:
                $this->message = Text::_('NEW_LIST_INVALID');
                break;

            case ERR_MISSING_LISTNAME:
                $this->message = Text::_('IMPORT_SELECT_TARGET');
                break;

            case ERR_MAX_LISTS_REACHED:
                $this->message = Text::_('NEW_LIST_MAX');
                break;

            default:
                $this->error    = ERR_IMPORT_NONE;
                $result['code'] = ERR_IMPORT_NONE;
                $this->message = Text::_('IMPORT_SUCCESS');
        }

        if ($format !== 'json') {
            if ($this->error != ERR_IMPORT_NONE)
                $this->page->sendError(Text::_('ERROR') . ' (' . $this->error . ')', $this->message);
            else
                $this->page->sendSuccess(Text::_('SUCCESS') . ' (' . $this->error . ')', $this->message);
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $this->error, 'message' => $this->message]);
            exit(0);
        }
    }

    protected function _save_ini($array, $file)
    {
        $content = '';
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $content .= '[' . $k . ']' . PHP_EOL;
                $content .= $this->_save_ini($v, null);
            } else
                $content .= $k . ' = ' . $v . PHP_EOL;
        }

        if ($file)
            return file_put_contents($file, $content);
        else
            return $content;
    }

    protected function _link()
    {
        $this->link = Factory::Link() . '/iptv/' . $this->params->folder . '/' . $this->params->folder;
        return $this->link;
    }
}
