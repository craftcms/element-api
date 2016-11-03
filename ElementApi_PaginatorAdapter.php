<?php
namespace Craft;

use League\Fractal\Pagination\PaginatorInterface;

class ElementApi_PaginatorAdapter implements PaginatorInterface
{
	/**
	 * @var integer
	 */
	protected $elementsPerPage;

	/**
	 * @var integer
	 */
	protected $totalElements;

	/**
	 * @var string
	 */
	protected $pageParam;

	/**
	 * @var integer
	 */
	protected $totalPages;

	/**
	 * @var integer
	 */
	protected $currentPage;

	/**
	 * @var integer
	 */
	protected $count;

	/**
	 * Constructor
	 *
	 * @param integer $elementsPerPage
	 * @param integer $totalElements
	 */
	public function __construct($elementsPerPage, $totalElements, $pageParam)
	{
		$this->elementsPerPage = $elementsPerPage;
		$this->totalElements = $totalElements;
		$this->pageParam = $pageParam;
		$this->totalPages = (int) ceil($this->totalElements / $this->elementsPerPage);
		$this->currentPage = $this->_getCurrentPage();
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
	 *
	 * @return string
	 */
	public function getUrl($page)
	{
		$params = craft()->request->getQuery();
		
		// This one belongs to Craft.
		unset($params['p']);

		$params[$this->pageParam] = $page;

		return UrlHelper::getUrl(craft()->request->getPath(), $params);
	}

	/**
	 * Determines the current page.
	 *
	 * @return integer
	 */
	private function _getCurrentPage()
	{
		$currentPage = craft()->request->getQuery($this->pageParam, 1);

		if (is_numeric($currentPage) && $currentPage > $this->totalPages)
		{
			$currentPage = $this->totalPages > 0 ? $this->totalPages : 1;
		}
		else if (!is_numeric($currentPage) || $currentPage < 0)
		{
			$currentPage = 1;
		}

		return (int) $currentPage;
	}
}
