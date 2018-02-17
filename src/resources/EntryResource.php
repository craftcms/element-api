<?php

namespace craft\elementapi\resources;

use Craft;
use craft\elementapi\PaginatorAdapter;
use craft\elements\Entry;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\ResourceInterface;
use yii\base\Exception;

/**
 * Entry resource adapter class.
 *
 * This works identically to [[ElementResource]], except that you don’t need to
 * specify `'elementType' => \craft\elements\Entry::class`, and it adds support
 * for `draftId` and `versionId` settings, for targeting a specific entry
 * draft or version.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class EntryResource extends ElementResource
{
    // Properties
    // =========================================================================

    /**
     * @var int|null The entry draft ID to return
     */
    public $draftId;

    /**
     * @var int|null The entry version ID to return
     */
    public $versionId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->elementType = Entry::class;
        parent::init();
    }

    /**
     * @inheritdoc
     * @throws Exception if [[one]] is true and no element matches [[criteria]]
     */
    public function getResource(): ResourceInterface
    {
        if ($this->draftId === null && $this->versionId === null) {
            return parent::getResource();
        }

        if ($this->draftId !== null) {
            $revision = Craft::$app->getEntryRevisions()->getDraftById($this->draftId);
        } else {
            $revision = Craft::$app->getEntryRevisions()->getVersionById($this->versionId);
        }

        if ($revision === null) {
            throw new Exception('No element exists that matches the endpoint criteria');
        }

        $transformer = $this->getTransformer();

        if ($this->one) {
            $resource = new Item($revision, $transformer, $this->resourceKey);
        } else {
            $resource = new Collection([$revision], $transformer, $this->resourceKey);

            if ($this->paginate) {
                $paginator = new PaginatorAdapter($this->elementsPerPage, 1, $this->pageParam);
                $paginator->setCount(1);
                $resource->setPaginator($paginator);
            }
        }

        if ($this->meta !== null) {
            $resource->setMeta($this->meta);
        }

        return $resource;
    }
}
