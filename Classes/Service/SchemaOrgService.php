<?php

declare(strict_types=1);

namespace Supseven\Schema\Service;

use Psr\Http\Message\ServerRequestInterface;
use Spatie\SchemaOrg\BaseType;
use Spatie\SchemaOrg\Contracts\ThingContract;
use Spatie\SchemaOrg\ItemList;
use Spatie\SchemaOrg\Schema;
use Supseven\Schema\Builder\SchemaBuilderInterface;
use Supseven\Schema\Builder\SiteSettingsSchemaBuilder;
use Supseven\Schema\SchemaContext;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class SchemaOrgService
 *
 * Coordinates Schema.org structured data generation.
 * Collects registered schema builders, applies them dynamically to incoming models,
 * generates a cohesive JSON-LD graph, and injects it into TYPO3's PageRenderer.
 */
class SchemaOrgService
{
    private const SCHEMA_CONTEXT_URL = 'https://schema.org';
    private const SCHEMA_BUILDER_TAG = 'schema.builder';
    private const DEFAULT_ITEM_LIST_NAME = 'ItemListName';

    /**
     * @var array<SchemaBuilderInterface> The registered builders.
     */
    private array $builders = [];

    /**
     * SchemaOrgService constructor.
     *
     * Injects the TYPO3 PageRenderer and gathers registered builders.
     *
     * @param PageRenderer $pageRenderer The TYPO3 PageRenderer service.
     * @param iterable<SchemaBuilderInterface> $builders Tagged services matching SchemaBuilderInterface.
     */
    public function __construct(
        protected PageRenderer $pageRenderer,
        #[AutowireIterator(self::SCHEMA_BUILDER_TAG)]
        iterable $builders = []
    ) {
        $this->builders = is_array($builders) ? $builders : iterator_to_array($builders, false);
    }

    /**
     * Adds structured data JSON-LD graph for one or multiple items.
     *
     * Automatically handles single model objects and collections (iterables).
     * If an iterable is passed, it wraps the individual items in a Schema.org ItemList.
     *
     * @param mixed $items A single model (e.g. Location, CourseProgram) or an iterable collection/QueryResult.
     * @param ServerRequestInterface $request The current request.
     */
    public function addStructuredData(mixed $items, ServerRequestInterface $request): void
    {
        $site = $request->getAttribute('site');

        if (!$site instanceof Site) {
            return;
        }

        $graph = $this->buildGraph($items, new SchemaContext($request, $site));

        if ($graph !== []) {
            $this->injectJsonLd($graph);
        }
    }

    /**
     * Builds all Schema.org graph elements for the provided input.
     *
     * @param mixed $items A single model, CType data array, QueryResult or typed item array.
     * @param SchemaContext $context The schema context.
     * @return array<BaseType>
     */
    private function buildGraph(mixed $items, SchemaContext $context): array
    {
        $graph = [];

        foreach ($this->builders as $builder) {
            if (!$builder instanceof SiteSettingsSchemaBuilder) {
                continue;
            }

            foreach ($builder->buildAll($context) as $schemaItem) {
                $graph[] = $schemaItem;
            }
        }

        if (is_iterable($items) && isset($items['CType'])) {
            $schemaItem = $this->buildSchemaItem($items, $context);

            if ($schemaItem !== null) {
                $graph[] = $schemaItem;
            }
        }

        if (is_iterable($items) && isset($items['doktype'])) {
            $schemaItem = $this->buildSchemaItem($items, $context);

            if ($schemaItem !== null) {
                $graph[] = $schemaItem;
            }
        }

        if ($items instanceof QueryResult) {
            $itemList = $this->buildItemList($items, $context);

            if ($itemList !== null) {
                $graph[] = $itemList;
            }
        }

        if (is_iterable($items) && isset($items['type'], $items['items']) && is_iterable($items['items'])) {
            $itemList = $this->buildItemList($items['items'], $context);

            if ($itemList !== null) {
                $graph[] = $itemList;
            }
        }

        if ($items instanceof AbstractEntity) {
            $schemaItem = $this->buildSchemaItem($items, $context);

            if ($schemaItem !== null) {
                $graph[] = $schemaItem;
            }
        }

        return $graph;
    }

    /**
     * Builds an ItemList schema from iterable items.
     *
     * @param iterable<mixed> $items Items to wrap in a Schema.org ItemList.
     * @param SchemaContext $context The schema context.
     * @return ItemList|null The generated ItemList schema, or null if no item could be mapped.
     */
    private function buildItemList(iterable $items, SchemaContext $context): ?ItemList
    {
        $listItems = [];
        $position = 1;
        $itemType = null;

        foreach ($items as $item) {
            $schemaItem = $this->buildSchemaItem($item, $context);

            if (!$schemaItem instanceof ThingContract) {
                continue;
            }

            if ($itemType === null && is_object($item)) {
                $itemType = $item::class;
            }

            $listItems[] = Schema::listItem()
                ->position($position++)
                ->item($schemaItem);
        }

        if ($listItems === []) {
            return null;
        }

        return Schema::itemList()
            ->name($this->getItemListName($itemType))
            ->numberOfItems(count($listItems))
            ->itemListElement($listItems);
    }

    /**
     * Dynamically finds a builder that supports the item and generates the schema.
     *
     * @param mixed $item The model to check.
     * @param SchemaContext $context The schema context.
     * @return BaseType|null The generated Schema object, or null if no builder was found.
     */
    private function buildSchemaItem(mixed $item, SchemaContext $context): ?BaseType
    {
        foreach ($this->builders as $builder) {
            if ($builder->supports($item)) {
                return $builder->build($item, $context);
            }
        }

        return null;
    }

    /**
     * @TODO: we might make this more generic or configurable to make lists "nameable"
     *
     * Returns the human-readable item list name.
     *
     * @param string|null $className The class name of the elements within the list.
     * @return string The localized item list name.
     */
    private function getItemListName(?string $className): string
    {
        if ($className === null) {
            return self::DEFAULT_ITEM_LIST_NAME;
        }

        return LocalizationUtility::translate(
            'LLL:EXT:schema/Resources/Private/Language/locallang.xlf:schema.title.' . $className,
            'schema'
        ) ?? self::DEFAULT_ITEM_LIST_NAME;
    }

    /**
     * Serializes the graph elements into JSON-LD and appends it to page header data.
     *
     * Removes duplicate contexts from graph sub-elements to form a clean @graph array.
     *
     * @param array<BaseType> $graph Array of Spatie SchemaOrg BaseType items.
     */
    private function injectJsonLd(array $graph): void
    {
        $serialized = array_map(static function (BaseType $type): array {
            $data = $type->toArray();
            unset($data['@context']);

            return $data;
        }, $graph);

        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;
        $nl = '';

        if (!Environment::getContext()->isProduction()) {
            $options |= JSON_PRETTY_PRINT;
            $nl = "\n";
        }

        $jsonLd = $nl . json_encode(
            [
                '@context' => self::SCHEMA_CONTEXT_URL,
                '@graph'   => $serialized,
            ],
            $options
        ) . $nl;

        $this->pageRenderer->addHeaderData('<script type="application/ld+json">' . $jsonLd . '</script>');
    }
}
