<?php

namespace CraftCms\ElementApi\Resources;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Typecast;
use CraftCms\ElementApi\Contracts\ResourceAdapter;
use CraftCms\ElementApi\Support\PaginatorAdapter;
use CraftCms\ElementApi\Transformers\ElementTransformer;
use Illuminate\Http\Request;
use InvalidArgumentException;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\ResourceInterface;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Element resource class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class ElementResource extends Component implements ResourceAdapter
{
    /**
     * @var class-string<ElementInterface> The element type that should be queried
     */
    public string $elementType;

    /**
     * @var array<string, mixed> The element query criteria
     */
    public array $criteria = [];

    /**
     * @var callable|class-string|array|TransformerAbstract The transformer that should be used
     */
    public $transformer = ElementTransformer::class;

    /**
     * @var bool Whether only the first element should be returned
     */
    public bool $one = false;

    /**
     * @var bool Whether the results should be paginated
     */
    public bool $paginate = true;

    /**
     * @var int The number of elements per page
     */
    public int $elementsPerPage = 100;

    /**
     * @var string The query string param that holds the page number
     */
    public string $pageParam = 'page';

    /**
     * @var string|null The resource key
     */
    public ?string $resourceKey = 'data';

    /**
     * @var array<string, mixed>|null Meta data to include in the response
     */
    public ?array $meta = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [], protected ?Request $request = null)
    {
        if (array_key_exists('first', $config)) {
            $config['one'] = Arr::pull($config, 'first');
            Deprecator::log('ElementAPI:first', 'The `first` Element API endpoint setting has been renamed to `one`.');
        }

        parent::__construct($config);

        $this->request ??= app(Request::class);

        if (!isset($this->elementType) || !is_subclass_of($this->elementType, ElementInterface::class)) {
            throw new InvalidArgumentException('Endpoint has an invalid elementType');
        }
    }

    /**
     * @inheritdoc
     */
    public function getResource(): ResourceInterface
    {
        $query = $this->getElementQuery();
        $transformer = $this->getTransformer();

        if ($this->one) {
            $element = $query->one();

            if (!$element) {
                throw new NotFoundHttpException('No element exists that matches the endpoint criteria');
            }

            $resource = new Item($element, $transformer, $this->resourceKey);
        } elseif ($this->paginate) {
            // Create the paginator
            $paginator = new PaginatorAdapter($this->elementsPerPage, $query->count(), $this->pageParam, $this->request);

            // Fetch this page's elements
            $query->offset(($query->offset ?: 0) + $this->elementsPerPage * ($paginator->getCurrentPage() - 1));
            $query->limit($this->elementsPerPage);
            $elements = $query->all();
            $paginator->setCount(count($elements));

            $resource = new Collection($elements, $transformer, $this->resourceKey);
            $resource->setPaginator($paginator);
        } else {
            $resource = new Collection($query->all(), $transformer, $this->resourceKey);
        }

        if ($this->meta !== null) {
            $resource->setMeta($this->meta);
        }

        return $resource;
    }

    /**
     * Returns the element query, configured with the endpoint's criteria.
     */
    protected function getElementQuery(): ElementQueryInterface
    {
        $elementType = $this->elementType;
        $query = $elementType::find();
        Typecast::configure($query, $this->criteria);

        return $query;
    }

    /**
     * Returns the transformer.
     */
    protected function getTransformer(): callable|TransformerAbstract
    {
        if (is_callable($this->transformer) || $this->transformer instanceof TransformerAbstract) {
            return $this->transformer;
        }

        if (is_array($this->transformer)) {
            $config = $this->transformer;
            $class = Arr::pull($config, 'class');

            return Typecast::configure(app($class), $config);
        }

        return app($this->transformer);
    }
}
