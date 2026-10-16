<?php

namespace CraftCms\ElementApi\Serializers;

use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\Request;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Serializer\ArraySerializer;

/**
 * JSON Feed v1 serializer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 * @link https://jsonfeed.org/version/1.1
 */
class JsonFeedV1Serializer extends ArraySerializer
{
    public function __construct(
        private readonly Request $request,
        private readonly Sites $sites,
    ) {}

    /**
     * @inheritdoc
     */
    public function collection($resourceKey, array $data): array
    {
        return ['items' => $data];
    }

    /**
     * @inheritdoc
     */
    public function meta(array $meta): array
    {
        return array_merge([
            'version' => 'https://jsonfeed.org/version/1.1',
            'title' => $this->sites->getCurrentSite()->name,
            'home_page_url' => Url::baseSiteUrl(),
            'feed_url' => Url::url($this->request->path()),
        ], $meta);
    }

    /**
     * @inheritdoc
     */
    public function paginator(PaginatorInterface $paginator): array
    {
        $currentPage = $paginator->getCurrentPage();
        $lastPage = $paginator->getLastPage();

        if ($currentPage < $lastPage) {
            return [
                'next_url' => $paginator->getUrl($currentPage + 1),
            ];
        }

        return [];
    }
}
