<?php

namespace Stripe;

/**
 * Search results for an API resource.
 *
 * This behaves similarly to <code>Collection</code> in that they both wrap
 * around a list of objects and provide pagination. However the
 * <code>SearchResult</code> object paginates by relying on a
 * <code>next_page</code> token included in the response rather than using
 * object IDs and a <code>starting_before</code>/<code>ending_after</code>
 * parameter. Thus, <code>SearchResult</code> only supports forwards pagination.
 *
 * The {@see $total_count} property is only available when
 * the `expand` parameter contains `total_count`.
 *
 * @template TStripeObject of StripeObject
 * @template-implements \IteratorAggregate<TStripeObject>
 *
 * @property string $object
 * @property string $url
 * @property string $next_page
 * @property int $total_count
 * @property bool $has_more
 * @property TStripeObject[] $data
 */
class SearchResult extends StripeObject implements \Countable, \IteratorAggregate
{
    const OBJECT_NAME = 'search_result';

    use ApiOperations\Request;

    /** @var array */
    protected $filters = [];

    /**
     * @return string the base URL for the given class
     */
    public static function baseUrl()
    {
        return Stripe::$apiBase;
    }

    /**
     * Returns the filters.
     *
     * @return array the filters
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Sets the filters, removing paging options.
     *
     * @param array $filters the filters
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
    }

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($k)
    {
        if (\is_string($k)) {
            return parent::offsetGet($k);
        }
        $msg = "You tried to access the {$k} index, but SearchResult " .
                   'types only support string keys. (HINT: Search calls ' .
                   'return an object with a `data` (which is the data ' .
                   "array). You likely want to call ->data[{$k}])";

        throw new Exception\InvalidArgumentException($msg);
    }

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws Exception\ApiErrorException
     *
     * @return SearchResult<TStripeObject>
     */
    public function all($params = null, $opts = null)
    {
        self::_validateParams($params);
        list($url, $params) = $this->extractPathAndUpdateParams($params);

        list($response, $opts) = $this->_request('get', $url, $params, $opts);
        $obj = Util\Util::convertToStripeObject($response, $opts);
        if (!($obj instanceof \Stripe\SearchResult)) {
            throw new \Stripe\Exception\UnexpectedValueException(
                'Expected type ' . \Stripe\SearchResult::class . ', got "' . \get_class($obj) . '" instead.'
            );
        }
        $obj->setFilters($params);

        return $obj;
    }

    /**
     * @return int the number of objects in the current page
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return \count($this->data);
    }

    /**
     * @return \ArrayIterator an iterator that can be used to iterate
     *    across objects in the current page
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * @return \Generator|TStripeObject[] A generator that can be used to
     *    iterate across all objects across all pages. As page boundaries are
     *    encountered, the next page will be fetched automatically for
     *    continued iteration.
     */
    public function autoPagingIterator()
    {
        $page = $this;

        while (true) {
            foreach ($page as $item) {
                yield $item;
            }
            $page = $page->nextPage();

            if ($page->isEmpty()) {
                break;
            }
        }
    }

    /**
     * Returns an empty set of search results. This is returned from
     * {@see nextPage()} when we know that there isn't a next page in order to
     * replicate the behavior of the API when it attempts to return a page
     * beyond the last.
     *
     * @param null|array|string $opts
     *
     * @return SearchResult
     */
    public static function emptySearchResult($opts = null)
    {
        return SearchResult::constructFrom(['data' => []], $opts);
    }

    /**
     * Returns true if the page object contains no element.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->data);
    }

    /**
     * Fetches the next page in the resource list (if there is one).
     *
     * This method will try to respect the limit of the current page. If none
     * was given, the default limit will be fetched again.
     *
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @return SearchResult<TStripeObject>
     */
    public function nextPage($params = null, $opts = null)
    {
        if (!$this->has_more) {
            return static::emptySearchResult($opts);
        }

        $params = \array_merge(
            $this->filters ?: [],
            ['page' => $this->next_page],
            $params ?: []
        );

        return $this->all($params, $opts);
    }

    /**
     * Gets the first item from the current page. Returns `null` if the current page is empty.
     *
     * @return null|TStripeObject
     */
    public function first()
    {
        return \count($this->data) > 0 ? $this->data[0] : null;
    }

    /**
     * Gets the last item from the current page. Returns `null` if the current page is empty.
     *
     * @return null|TStripeObject
     */
    public function last()
    {
        return \count($this->data) > 0 ? $this->data[\count($this->data) - 1] : null;
    }

    private function extractPathAndUpdateParams($params)
    {
        $url = \parse_url($this->url);

        if (!isset($url['path'])) {
            throw new Exception\UnexpectedValueException("Could not parse list url into parts: {$url}");
        }

        if (isset($url['query'])) {
            // If the URL contains a query param, parse it out into $params so they
            // don't interact weirdly with each other.
            $query = [];
            \parse_str($url['query'], $query);
            $params = \array_merge($params ?: [], $query);
        }

        return [$url['path'], $params];
    }
}
