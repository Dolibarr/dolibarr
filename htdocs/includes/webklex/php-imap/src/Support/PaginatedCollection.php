<?php
/*
* File:     PaginatedCollection.php
* Category: Collection
* Author:   M. Goldenbaum
* Created:  16.03.18 03:13
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;

/**
 * Class PaginatedCollection
 *
 * @package Webklex\PHPIMAP\Support
 */
class PaginatedCollection extends Collection {

    /**
     * Number of total entries
     *
     * @var int $total
     */
    protected $total;

    /**
     * Paginate the current collection.
     * @param int      $per_page
     * @param int|null $page
     * @param string   $page_name
     * @param boolean  $prepaginated
     *
     * @return LengthAwarePaginator
     */
    public function paginate($per_page = 15, $page = null, $page_name = 'page', $prepaginated = false) {
        $page = $page ?: Paginator::resolveCurrentPage($page_name);

        $total = $this->total ? $this->total : $this->count();

        $results = !$prepaginated && $total ? $this->forPage($page, $per_page) : $this->all();

        return $this->paginator($results, $total, $per_page, $page, [
            'path'      => Paginator::resolveCurrentPath(),
            'pageName'  => $page_name,
        ]);
    }

    /**
     * Create a new length-aware paginator instance.
     * @param  array    $items
     * @param  int      $total
     * @param  int      $per_page
     * @param  int|null $current_page
     * @param  array    $options
     *
     * @return LengthAwarePaginator
     */
    protected function paginator($items, $total, $per_page, $current_page, array $options) {
        return new LengthAwarePaginator($items, $total, $per_page, $current_page, $options);
    }

    /**
     * Get and set the total amount
     * @param null $total
     *
     * @return int|null
     */
    public function total($total = null) {
        if($total === null) {
            return $this->total;
        }

        return $this->total = $total;
    }
}
