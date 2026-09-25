<?php

declare(strict_types=1);

namespace Supseven\Schema\Builder;

use Spatie\SchemaOrg\BaseType;
use Supseven\Schema\SchemaContext;

/**
 * Interface SchemaBuilderInterface
 *
 * Defines the contract for all Schema.org structured data builders.
 * Concrete implementations must specify what types of models they support
 * and contain the logic to transform those models into Spatie Schema.org objects.
 */
interface SchemaBuilderInterface
{
    /**
     * Checks if this builder supports the given item.
     *
     * @param mixed $item The model or object to check.
     * @return bool True if the builder supports the item, false otherwise.
     */
    public function supports(mixed $item): bool;

    /**
     * Builds the Schema.org object representation for the given item within the specified context.
     *
     * @param mixed $item The model to map to structured data.
     * @param SchemaContext $context The schema context holding request/site configurations.
     * @return BaseType|null The constructed Schema.org object, or null if mapping fails or is skipped.
     */
    public function build(mixed $item, SchemaContext $context): ?BaseType;
}
