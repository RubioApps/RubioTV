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
use RubioTV\Framework\SEF;
use RubioTV\Framework\Language\Text;

/**
 * https://www.tdtchannels.com/lists/radio.m3u
 * https://raw.githubusercontent.com/Tundrak/IPTV-Italia/main/ipradioita.m3u
 * 
 */
class modelStations extends Model
{
    protected $m3u;
    protected $error;
    protected $message;

    public function display()
    {
        // Available stations in a given source
        $this->params->source_alias = $this->params->source_alias ?? SEF::rfind($this->params->folder, $this->params->source);

        $this->page->title          = Text::_('GROUPS')[strtoupper($this->params->source)] ?? ucfirst(SEF::decode($this->params->source_alias));
        $this->page->folder         = $this->params->folder;
        $this->page->source         = $this->params->source;
        $this->page->source_alias   = $this->params->source_alias;
        $this->page->data           = $this->_data();
        $this->page->link           = $this->_link();

        if (!$this->page->data) {
            $this->page->sendError(Text::_('ERROR'), Text::_('ERROR_FOLDER'));
            return false;
        };

        // Build pagination
        $this->page->pagination = $this->_pagination();

        // Defered function Factory::Link for performance purposes
        foreach ($this->page->data as $e) {
            $e->link    = Factory::Link(
                'listen',
                $this->params->folder,
                $this->params->source . ':' . $this->params->source_alias,
                $e->id . ':' . $e->name
            );

            if (empty($e->remote))
                $e->remote  = Factory::Link(
                    'image.remote',
                    $this->params->folder,
                    $this->params->source,
                    $e->id,
                    'url=' . base64_encode($e->logo),
                    'cache=stations'
                );
        }

        parent::display();
    }

    public function search()
    {
        $this->params->source_alias = $this->params->source_alias ?? SEF::rfind($this->params->folder, $this->params->source);

        if ($this->_data() && $this->params->format === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            echo $this->_term();
            exit(0);
        }
    }

    /**
     * Edit a custom list, and allow to sort and delete
     */

    public function edit()
    {
        return;
        
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
            echo json_encode(['error' => !$this->error, 'message' => $this->message]);
            exit(0);
        }

        $this->page->title      = Text::_($this->params->source);
        $this->page->folder     = $this->params->folder;
        $this->page->source     = $this->params->source;
        $this->page->alias      = $this->params->source_alias ?? SEF::rfind($this->params->folder, $this->params->source);
        $this->page->pagination = $this->_pagination();
        $this->page->data       = $this->data;
        $this->page->link       = $this->_link();

        // Build pagination
        $this->page->pagination = $this->_pagination();

        $this->page->setFile('radio.edit.php');

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

        $this->m3u   = new M3U($this->params->folder, $this->params->source);
        $this->m3u->load();

        if (!$this->error && isset($post['ids'])) {
            $this->data = $this->_data();
            $this->error = $this->m3u->remove($post['ids']);
            $this->message = 'Remove items';
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => !$this->error, 'message' => $this->message]);
        exit(0);
    }

    /**
     * Get the associative array of radio channels
     */
    protected function _data()
    {

        $m3u = new M3U($this->params->folder, $this->params->source);
        $this->data = $m3u->load();
        return $this->data;
    }

    protected function _link()
    {
        $config = Factory::getConfig();
        $this->link = $config->live_site . '/iptv/stations/' . $this->params->source . '.m3u';
        return $this->link;
    }

}
