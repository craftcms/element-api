<?php

namespace craft\elementapi;

use Craft;
use craft\helpers\UrlHelper;
use League\Fractal\Pagination\PaginatorInterface;

/**
 * Fractal paginator adapter class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class PaginatorAdapter implements PaginatorInterface
{
    /**
     * @var int
     */
    protected $elementsPerPage;

    /**
     * @var int
     */
    protected $totalElements;

    /**
     * @var string
     */
    protected $pageParam;

    /**
     * @var int
     */
    protected $totalPages;

    /**
     * @var int
     */
    protected $currentPage;

    /**
     * @var int
     */
    protected $count;

    /**
     * Constructor
     *
     * @param integer $elementsPerPage
     * @param integer $totalElements
     * @param string $pageParam
     */
    public function __construct($elementsPerPage, $totalElements, $pageParam)
    {
        $this->elementsPerPage = $elementsPerPage;
        $this->totalElements = $totalElements;
        $this->pageParam = $pageParam;
        $this->totalPages = (int)ceil($this->totalElements / $this->elementsPerPage);
        $this->currentPage = $this->_currentPage();
    }

    /**
     * Get the current page.
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * Get the last page.
     *
     * @return int
     */
    public function getLastPage()
    {
        return $this->totalPages;
    }

    /**
     * Get the total.
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->totalElements;
    }

    /**
     * Get the count.
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Sets the count.
     *
     * @param int $count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    /**
     * Get the number per page.
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->elementsPerPage;
    }

    /**
     * Get the url for the given page.
     *
     * @param int $page
     * @return string
     */
    public function getUrl($page)
    {
        $request = Craft::$app->getRequest();

        // Get the query string params without the path or `pattern`
        parse_str($request->getQueryString(), $params);
        $pathParam = Craft::$app->getConfig()->getGeneral()->pathParam;
        unset($params[$pathParam], $params['pattern']);

        // Return the URL with the page param
        $params[$this->pageParam] = $page;
        return UrlHelper::url($request->getPathInfo(), $params);
    }

    /**
     * Determines the current page.
     *
     * @return integer
     */
    private function _currentPage(): int
    {
        $currentPage = Craft::$app->getRequest()->getQueryParam($this->pageParam, 1);

        if (is_numeric($currentPage) && $currentPage > $this->totalPages) {
            $currentPage = $this->totalPages > 0 ? $this->totalPages : 1;
        } else if (!is_numeric($currentPage) || $currentPage < 0) {
            $currentPage = 1;
        }

        return (int)$currentPage;
    }
}
