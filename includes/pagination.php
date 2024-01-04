<?php
namespace RubioTV\Framework;

use RubioTV\Framework\Language\Text;

defined('_TVEXEC') or die;

class Pagination
{
    public $config = null;
    public $offset= null;
    public $limit = null;
    public $total = null;
    public $pagesStart;
    public $pagesStop;
    public $pagesCurrent;
    public $pagesTotal;
    public $hideEmptyLimitstart = false;

    protected $viewall = false;
    protected $additionalUrlParams = array();
    protected $data;

    public function __construct($total, $offset, $limit)
    {
        $this->config = Factory::getConfig();
        
        // Value/type checking.
        $this->total = (int) $total;
        $this->offset = (int) max($offset, 0);
        $this->limit = (int) max($limit, 0);

        if ($this->limit > $this->total) {
            $this->offset = 0;
        }

        if (!$this->limit) {
            $this->limit = $total;
            $this->offset = 0;
        }

        /*
         * If offset is greater than total (i.e. we are asked to display records that don't exist)
         * then set offset to display the last natural page of results
         */
        if ($this->offset > $this->total - $this->limit) {
            $this->offset = max(0, (int) (ceil($this->total / $this->limit) - 1) * $this->limit);
        }

        // Set the total pages and current page values.
        if ($this->limit > 0) {
            $this->pagesTotal = (int) ceil($this->total / $this->limit);
            $this->pagesCurrent = (int) ceil(($this->offset + 1) / $this->limit);
        }

        // Set the pagination iteration loop values.
        $displayedPages = 10;
        $this->pagesStart = $this->pagesCurrent - ($displayedPages / 2);

        if ($this->pagesStart < 1) {
            $this->pagesStart = 1;
        }

        if ($this->pagesStart + $displayedPages > $this->pagesTotal) {
            $this->pagesStop = $this->pagesTotal;

            if ($this->pagesTotal < $displayedPages) {
                $this->pagesStart = 1;
            } else {
                $this->pagesStart = $this->pagesTotal - $displayedPages + 1;
            }
        } else {
            $this->pagesStop = $this->pagesStart + $displayedPages - 1;
        }

        // If we are viewing all records set the view all flag to true.
        if ($limit === 0) {
            $this->viewall = true;
        }
    }

    /**
     * Method to set an additional URL parameter to be added to all pagination class generated
     * links.
     *
     * @param   string  $key    The name of the URL parameter for which to set a value.
     * @param   mixed   $value  The value to set for the URL parameter.
     *
     * @return  mixed  The old value for the parameter.
     *
     * @since   1.6
     */
    public function setAdditionalUrlParam($key, $value)
    {
        // Get the old value to return and set the new one for the URL parameter.
        $result = $this->additionalUrlParams[$key] ?? null;

        // If the passed parameter value is null unset the parameter, otherwise set it to the given value.
        if ($value === null || !strlen($value)) {
            unset($this->additionalUrlParams[$key]);
        } else {
            $this->additionalUrlParams[$key] = $value;
        }

        return $result;
    }

    /**
     * Method to get an additional URL parameter (if it exists) to be added to
     * all pagination class generated links.
     *
     * @param   string  $key  The name of the URL parameter for which to get the value.
     *
     * @return  mixed  The value if it exists or null if it does not.
     *
     * @since   1.6
     */
    public function getAdditionalUrlParam($key)
    {
        return $this->additionalUrlParams[$key] ?? null;
    }

    /**
     * Return the rationalised offset for a row with a given index.
     *
     * @param   integer  $index  The row index
     *
     * @return  integer  Rationalised offset for a row with a given index.
     *
     * @since   1.5
     */
    public function getRowOffset($index)
    {
        return $index + 1 + $this->offset;
    }

    /**
     * Return the pagination data object, only creating it if it doesn't already exist.
     *
     * @return  \stdClass  Pagination data object.
     *
     * @since   1.5
     */
    public function getData()
    {
        if (!$this->data) {
            $this->data = $this->_buildDataObject();
        }

        return $this->data;
    }

    /**
     * Create and return the pagination pages counter string, ie. Page 2 of 4.
     *
     * @return  string   Pagination pages counter string.
     *
     * @since   1.5
     */
    public function getPagesCounter()
    {
        $html = null;

        if ($this->pagesTotal > 1) {
            $html .= Text::sprintf('CURRENT_PAGE', $this->pagesCurrent, $this->pagesTotal);
        }

        return $html;
    }

    /**
     * Create and return the pagination result set counter string, e.g. Results 1-10 of 42
     *
     * @return  string   Pagination result set counter string.
     *
     * @since   1.5
     */
    public function getResultsCounter()
    {
        $html = null;
        $fromResult = $this->offset + 1;

        // If the limit is reached before the end of the list.
        if ($this->offset + $this->limit < $this->total) {
            $toResult = $this->offset + $this->limit;
        } else {
            $toResult = $this->total;
        }

        // If there are results found.
        if ($this->total > 0) {
            $msg = Text::sprintf('RESULTS_OF', $fromResult, $toResult, $this->total);
            $html .= "\n" . $msg;
        } else {
            $html .= "\n" . Text::_('NOT_RESULT_FOUND');
        }

        return $html;
    }

    /**
     * Create and return the pagination page list string, ie. Previous, Next, 1 2 3 ... x.
     *
     * @return  string  Pagination page list string.
     *
     * @since   1.5
     */
    public function getPagesLinks()
    {
        // Build the page navigation list.
        $data = $this->_buildDataObject();

        $list           = array();

        // Build the select list
        if ($data->all->base !== null) {
            $list['all']['active'] = true;
            $list['all']['data']   = $this->_item_active($data->all);
        } else {
            $list['all']['active'] = false;
            $list['all']['data']   = $this->_item_inactive($data->all);
        }

        if ($data->start->base !== null) {
            $list['start']['active'] = true;
            $list['start']['data']   = $this->_item_active($data->start);
        } else {
            $list['start']['active'] = false;
            $list['start']['data']   = $this->_item_inactive($data->start);
        }

        if ($data->previous->base !== null) {
            $list['previous']['active'] = true;
            $list['previous']['data']   = $this->_item_active($data->previous);
        } else {
            $list['previous']['active'] = false;
            $list['previous']['data']   = $this->_item_inactive($data->previous);
        }

        // Make sure it exists
        $list['pages'] = array();

        foreach ($data->pages as $i => $page) {
            if ($page->base !== null) {
                $list['pages'][$i]['active'] = true;
                $list['pages'][$i]['data']   = $this->_item_active($page);
            } else {
                $list['pages'][$i]['active'] = false;
                $list['pages'][$i]['data']   = $this->_item_inactive($page);
            }
        }

        if ($data->next->base !== null) {
            $list['next']['active'] = true;
            $list['next']['data']   = $this->_item_active($data->next);
        } else {
            $list['next']['active'] = false;
            $list['next']['data']   = $this->_item_inactive($data->next);
        }

        if ($data->end->base !== null) {
            $list['end']['active'] = true;
            $list['end']['data']   = $this->_item_active($data->end);
        } else {
            $list['end']['active'] = false;
            $list['end']['data']   = $this->_item_inactive($data->end);
        }

        if ($this->total > $this->limit) {
            return $this->_list_render($list);
        } else {
            return '';
        }
    }

    protected function _list_footer($list)
    {
        $html = "<div class=\"list-footer\">\n";

        $html .= "\n<div class=\"limit\">" . Text::_('DISPLAY_NUM') . $list['limitfield'] . "</div>";
        $html .= $list['pageslinks'];
        $html .= "\n<div class=\"counter\">" . $list['pagescounter'] . "</div>";

        $html .= "\n<input type=\"hidden\" name=\"offset\" value=\"" . $list['offset'] . "\">";
        $html .= "\n</div>";

        return $html;
    }

    protected function _list_render($list)
    {   

        $chromePath = TV_THEMES . DIRECTORY_SEPARATOR . $this->config->theme . DIRECTORY_SEPARATOR . 'pagination.php';

        if (is_file($chromePath)) {
            include_once $chromePath;
            if (\function_exists('pagination_list_render')) {
                return pagination_list_render($list);
            }
        }
        
        // Initialize variables
	$html = null;

	// Reverse output rendering for right-to-left display
	$html .= '&lt;&lt; ';
	$html .= $list['start']['data'];
	$html .= ' &lt; ';
	$html .= $list['previous']['data'];
	foreach( $list['pages'] as $page ) {
            $html .= ' '.$page['data'];
	}
	$html .= ' '. $list['next']['data'];
	$html .= ' &gt;';
	$html .= ' '. $list['end']['data'];
	$html .= ' &gt;&gt;';

	return $html;
    }

    protected function _item_active(PaginationObject $item)
    {

        $chromePath =TV_THEMES . DIRECTORY_SEPARATOR . $this->config->theme . DIRECTORY_SEPARATOR . 'pagination.php';

        if (is_file($chromePath)) {
            include_once $chromePath;
            if (\function_exists('pagination_item_active')) {
                return pagination_item_active($item);
            }
        }        
        return '<a href="' . $item->link . '">' . $item->text . '</a>';
    }

    protected function _item_inactive(PaginationObject $item)
    {
        $chromePath = TV_THEMES . DIRECTORY_SEPARATOR . $this->config->theme . DIRECTORY_SEPARATOR . 'pagination.php';

        if (is_file($chromePath)) {
            include_once $chromePath;
            if (\function_exists('pagination_item_inactive')) {
                return pagination_item_inactive($item);
            }
        }           
        return '<a href="' . $item->link . '">' . $item->text . '</a>';
    }

    protected function _buildDataObject()
    {
        $data = new \stdClass();

        // Build the additional URL parameters string.
        $params = '';

        if (!empty($this->additionalUrlParams)) {
            foreach ($this->additionalUrlParams as $key => $value) {
                $params .= ($params !='' ? '&' : '?') . $key . '=' . $value;
            }
        } 
        
        $data->all = new PaginationObject(Text::_('VIEW_ALL'));

        if (!$this->viewall) {
            $data->all->base = '0';
            $data->all->link = $params . '&offset=';
        }

        // Set the start and previous data objects.
        $data->start    = new PaginationObject(Text::_('START'));
        $data->previous = new PaginationObject(Text::_('PREV'));

        if ($this->pagesCurrent > 1) {
            $page = ($this->pagesCurrent - 2) * $this->limit;

            if ($this->hideEmptyLimitstart) {
                $data->start->link =  $params . '&offset=';
            } else {
                $data->start->link =  $params . '&offset=0';
            }

            $data->start->base    = '0';
            $data->previous->base = $page;

            if ($page === 0 && $this->hideEmptyLimitstart) {
                $data->previous->link = $data->start->link;
            } else {
                $data->previous->link = $params . '&offset=' . $page;
            }
        }

        // Set the next and end data objects.
        $data->next = new PaginationObject(Text::_('NEXT'));
        $data->end  = new PaginationObject(Text::_('END'));

        if ($this->pagesCurrent < $this->pagesTotal) {
            $next = $this->pagesCurrent * $this->limit;
            $end  = ($this->pagesTotal - 1) * $this->limit;

            $data->next->base = $next;
            $data->next->link =  $params . '&offset=' . $next;
            $data->end->base  = $end;
            $data->end->link  =  $params . '&offset=' . $end;
        }

        $data->pages = array();
        $stop        = $this->pagesStop;

        for ($i = $this->pagesStart; $i <= $stop; $i++) {
            $offset = ($i - 1) * $this->limit;

            $data->pages[$i] = new PaginationObject($i);

            if ($i != $this->pagesCurrent || $this->viewall) {
                $data->pages[$i]->base = $offset;

                if ($offset === 0 && $this->hideEmptyLimitstart) {
                    $data->pages[$i]->link = $data->start->link;
                } else {
                    $data->pages[$i]->link =  $params . '&offset=' . $offset;
                }
            } else {
                $data->pages[$i]->active = true;
            }
        }

        return $data;
    }
}

class PaginationObject
{
    public $text;
    public $base;
    public $link;
    public $active;

    public function __construct($text, $base = null, $link = null, $active = false)
    {
        $this->text   = $text;
        $this->base   = $base;
        $this->link   = $link;
        $this->active = $active;
    }
}