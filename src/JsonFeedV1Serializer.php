<?php

namespace craft\elementapi;

use Craft;
use craft\helpers\UrlHelper;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Serializer\ArraySerializer;

/**
 * Serializer for JSON Feed: Version 1
 *
 * @see https://jsonfeed.org/version/1
 */
class JsonFeedV1Serializer extends ArraySerializer
{
    /**
     * @inheritdoc
     */
    public function collection($resourceKey, array $data)
    {
        return ['items' => $data];
    }

    /**
     * @inheritdoc
     */
    public function meta(array $meta)
    {
        return array_merge([
            'version' => 'https://jsonfeed.org/version/1',
            'title' => \Craft::$app->getSites()->getCurrentSite()->name,
            'home_page_url' => UrlHelper::baseSiteUrl(),
            'feed_url' => UrlHelper::url(Craft::$app->getRequest()->getPathInfo()),
        ], $meta);
    }

    /**
     * @inheritdoc
     */
    public function paginator(PaginatorInterface $paginator)
    {
        $currentPage = (int)$paginator->getCurrentPage();
        $lastPage = (int)$paginator->getLastPage();

        if ($currentPage < $lastPage) {
            return [
                'next_url' => $paginator->getUrl($currentPage + 1)
            ];
        }

        return [];
    }
}
