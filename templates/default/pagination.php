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

defined('_TVEXEC') or die;

use RubioTV\Framework\SEF;
use RubioTV\Framework\Language\Text;

function pagination_list_render($list , $onlyarrows = false)
{
	// Initialize variables
	$html = '<ul class="pagination justify-content-center">';

	if(!$onlyarrows)
		if ($list['start']['active']==1)   $html .= $list['start']['data'];

	if ($list['previous']['active']==1) $html .= $list['previous']['data'];

	if(!$onlyarrows)
	{
		foreach ($list['pages'] as $page) 
			$html .= $page['data'];
	}

	if ($list['next']['active']==1) $html .= $list['next']['data'];

	if(!$onlyarrows)
		if ($list['end']['active']==1)  $html .= $list['end']['data'];

    $html .= '</ul>';
	return $html;
}

function pagination_item_active(&$item)
{

    $cls = '';

    if ($item->text == Text::_('PREV')) { $item->text = '&laquo;'; $cls = ' d-sm-block';}
    if ($item->text == Text::_('NEXT')) { $item->text = '&raquo;'; $cls = ' d-sm-block';}
    
    if ($item->text == Text::_('START')) { $cls = '';}
    if ($item->text == Text::_('END'))   { $cls = '';}

	if($cls=='') $cls = ' d-none d-md-block';
    
    return '<li class="page-item' . $cls . '"><a class="page-link" href="' . SEF::_($item->link) . '">' . $item->text . '</a></li>';
}

function pagination_item_inactive( &$item )
{
    if ($item->text == Text::_('PREV')) { $item->text = '&laquo;';}
    if ($item->text == Text::_('NEXT')) { $item->text = '&raquo;';}

	return '<li class="page-item disabled page-link">' . $item->text . '</li>';
}
