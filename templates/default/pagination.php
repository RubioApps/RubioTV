<?php
defined('_TVEXEC') or die;

use RubioTV\Framework\Language\Text;

function pagination_list_render($list)
{
	// Initialize variables
	$html = '<ul class="pagination justify-content-center">';

	if ($list['start']['active']==1)   $html .= $list['start']['data'];
	if ($list['previous']['active']==1) $html .= $list['previous']['data'];

	foreach ($list['pages'] as $page) {
		$html .= $page['data'];
	}
	if ($list['next']['active']==1) $html .= $list['next']['data'];
	if ($list['end']['active']==1)  $html .= $list['end']['data'];

        $html .= '</ul>';
	return $html;
}

function pagination_item_active(&$item)
{

    $cls = '';

    if ($item->text == Text::_('PREV')) { $item->text = '&laquo;'; $cls = '';}
    if ($item->text == Text::_('NEXT')) { $item->text = '&raquo;'; $cls = '';}
    
    if ($item->text == Text::_('START')) { $cls = ' first';}
    if ($item->text == Text::_('END'))   { $cls = ' last';}
    
    return '<li class="page-item' . $cls . '"><a class="page-link" href="' . $item->link . '">' . $item->text . '</a></li>';
}

function pagination_item_inactive( &$item )
{
	$cls = (int)$item->text > 0 ? 'active': '';
	return '<li class="page-item ' . $cls . '"><a class="page-link" href="#">' . $item->text . '</a></li>';
}
