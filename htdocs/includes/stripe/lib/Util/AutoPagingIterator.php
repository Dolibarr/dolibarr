<?php

namespace Stripe\Util;

class AutoPagingIterator implements \Iterator
{
    private $lastId = null;
    private $page = null;
    private $pageOffset = 0;
    private $params = [];

    public function __construct($collection, $params)
    {
        $this->page = $collection;
        $this->params = $params;
    }

    public function rewind()
    {
        // Actually rewinding would require making a copy of the original page.
    }

    public function current()
    {
        $item = current($this->page->data);
        $this->lastId = $item !== false ? $item['id'] : null;

        return $item;
    }

    public function key()
    {
        return key($this->page->data) + $this->pageOffset;
    }

    public function next()
    {
        $item = next($this->page->data);
        if ($item === false) {
            // If we've run out of data on the current page, try to fetch another one
            // and increase the offset the new page would start at
            $this->pageOffset += count($this->page->data);
            if ($this->page['has_more']) {
                $this->params = array_merge(
                    $this->params ?: [],
                    ['starting_after' => $this->lastId]
                );
                $this->page = $this->page->all($this->params);
            } else {
                return false;
            }
        }
    }

    public function valid()
    {
        $key = key($this->page->data);
        $valid = ($key !== null && $key !== false);
        return $valid;
    }
}
