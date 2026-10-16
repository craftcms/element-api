<?php

namespace CraftCms\ElementApi\Support;

use CraftCms\Cms\Support\Url;
use Illuminate\Http\Request;
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
    protected int $elementsPerPage;

    /**
     * @var int
     */
    protected int $totalElements;

    /**
     * @var string
     */
    protected string $pageParam;

    /**
     * @var int
     */
    protected int $totalPages;

    /**
     * @var int
     */
    protected int $currentPage;

    /**
     * @var int
     */
    protected int $count;

    /**
     * Constructor
     *
     * @param integer $elementsPerPage
     * @param integer $totalElements
     * @param string $pageParam
     */
    public function __construct(
        int $elementsPerPage,
        int $totalElements,
        string $pageParam,
        protected Request $request,
    ) {
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
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Get the last page.
     *
     * @return int
     */
    public function getLastPage(): int
    {
        return $this->totalPages;
    }

    /**
     * Get the total.
     *
     * @return int
     */
    public function getTotal(): int
    {
        return $this->totalElements;
    }

    /**
     * Get the count.
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Sets the count.
     *
     * @param int $count
     */
    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    /**
     * Get the number per page.
     *
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->elementsPerPage;
    }

    /**
     * Get the url for the given page.
     *
     * @param int $page
     * @return string
     */
    public function getUrl(int $page): string
    {
        // Get the query string params
        $params = $this->request->query();

        // Return the URL with the page param
        $params[$this->pageParam] = $page;

        return Url::url($this->request->path(), $params);
    }

    /**
     * Determines the current page.
     *
     * @return integer
     */
    private function _currentPage(): int
    {
        $currentPage = $this->request->query($this->pageParam, 1);

        if (is_numeric($currentPage) && $currentPage > $this->totalPages) {
            $currentPage = $this->totalPages > 0 ? $this->totalPages : 1;
        } elseif (!is_numeric($currentPage) || $currentPage < 0) {
            $currentPage = 1;
        }

        return (int)$currentPage;
    }
}
