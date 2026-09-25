<?php

declare(strict_types=1);

namespace Supseven\Schema\Builder;

use Spatie\SchemaOrg\BaseType;
use Supseven\Schema\SchemaContext;
use Supseven\Schema\Utility\Util;
use TYPO3\CMS\Core\Site\Entity\Site;

readonly class SiteSettingsSchemaBuilder implements SchemaBuilderInterface
{
    private const SETTING_PATH_SCHEMA = 'schema';
    private const CONFIG_KEY_TYPE = 'type';
    private const CONFIG_KEY_SHARED = 'shared';
    private const SPATIE_SCHEMA_NAMESPACE = 'Spatie\\SchemaOrg\\';

    public function supports(mixed $item): bool
    {
        return is_array($item)
            && ($item['_schema_source'] ?? '') === 'site-settings';
    }

    public function build(mixed $item, SchemaContext $context): ?BaseType
    {
        $schemas = $this->buildAll($context);

        return $schemas[0] ?? null;
    }

    /**
     * @return array<BaseType>
     */
    public function buildAll(SchemaContext $context): array
    {
        $site = $context->getRequest()->getAttribute('site');

        if (!$site instanceof Site) {
            $site = $context->getSite();
        }

        $schemaSettings = $this->getSchemaSettings($site);

        if ($schemaSettings === []) {
            return [];
        }

        $schemas = [];

        foreach ($schemaSettings as $schemaKey => $schemaConfig) {

            if ($schemaKey === self::CONFIG_KEY_SHARED || !is_array($schemaConfig)) {
                continue;
            }

            if (!isset($schemaConfig[self::CONFIG_KEY_TYPE])) {
                continue;
            }

            $schema = $this->buildSchemaFromConfig($schemaConfig, $schemaSettings);

            if (!$schema instanceof BaseType) {
                continue;
            }

            $this->applyDefaultProperties($schema, (string)$schemaKey, $site);

            $schemas[] = $schema;
        }

        return $schemas;
    }

    /**
     * @return array<string, mixed>
     */
    private function getSchemaSettings(Site $site): array
    {
        $settings = $site->getSettings()->getAll();

        $schemaSettings = $settings[self::SETTING_PATH_SCHEMA] ?? [];

        if (!is_array($schemaSettings)) {
            return [];
        }

        return $schemaSettings;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $rootSettings
     */
    private function buildSchemaFromConfig(array $config, array $rootSettings): ?BaseType
    {
        $type = trim((string)($config[self::CONFIG_KEY_TYPE] ?? ''));

        if ($type === '') {
            return null;
        }

        $className = $this->resolveSchemaClassName($type);

        if ($className === null) {
            return null;
        }

        /** @var BaseType $schema */
        $schema = new $className();

        foreach ($config as $propertyName => $propertyValue) {
            if ($propertyName === self::CONFIG_KEY_TYPE) {
                continue;
            }

            $resolvedValue = $this->resolveValue($propertyValue, $rootSettings);

            if ($resolvedValue === null || $resolvedValue === '') {
                continue;
            }

            $schema->setProperty(
                (string)$propertyName,
                $resolvedValue
            );
        }

        return $schema;
    }

    private function resolveSchemaClassName(string $type): ?string
    {
        $className = str_contains($type, '\\')
            ? $type
            : self::SPATIE_SCHEMA_NAMESPACE . $type;

        if (!class_exists($className)) {
            return null;
        }

        if (!is_a($className, BaseType::class, true)) {
            return null;
        }

        return $className;
    }

    /**
     * @param array<string, mixed> $rootSettings
     */
    private function resolveValue(mixed $value, array $rootSettings): mixed
    {
        if (is_string($value)) {
            return $this->resolvePlaceholder($value, $rootSettings);
        }

        if (!is_array($value)) {
            return $value;
        }

        if (isset($value[self::CONFIG_KEY_TYPE])) {
            return $this->buildSchemaFromConfig($value, $rootSettings);
        }

        $isList = $this->isListArray($value);

        $resolvedValues = [];

        foreach ($value as $key => $nestedValue) {
            $resolvedValue = $this->resolveValue($nestedValue, $rootSettings);

            if ($resolvedValue === null || $resolvedValue === '') {
                continue;
            }

            if ($isList) {
                $resolvedValues[] = $resolvedValue;
                continue;
            }

            $resolvedValues[$key] = $resolvedValue;
        }

        if ($isList) {
            return array_values($resolvedValues);
        }

        return $resolvedValues;
    }

    /**
     * @param array<mixed> $value
     */
    private function isListArray(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        if (array_is_list($value)) {
            return true;
        }

        return $this->hasOnlySequentialNumericKeys($value);
    }

    /**
     * @param array<mixed> $value
     */
    private function hasOnlySequentialNumericKeys(array $value): bool
    {
        $keys = array_keys($value);

        foreach ($keys as $key) {
            if (!is_int($key) && !(is_string($key) && ctype_digit($key))) {
                return false;
            }
        }

        $normalizedKeys = array_map('intval', $keys);
        sort($normalizedKeys);

        return $normalizedKeys === range(0, count($normalizedKeys) - 1);
    }

    /**
     * @param array<string, mixed> $rootSettings
     */
    private function resolvePlaceholder(string $value, array $rootSettings): string
    {
        if (!str_starts_with($value, '%') || !str_ends_with($value, '%')) {
            return $value;
        }

        $path = trim($value, '%');
        $pathParts = explode('.', $path);

        if (($pathParts[0] ?? '') === self::SETTING_PATH_SCHEMA) {
            array_shift($pathParts);
        }

        $resolvedValue = $rootSettings;

        foreach ($pathParts as $pathPart) {
            if (!is_array($resolvedValue) || !array_key_exists($pathPart, $resolvedValue)) {
                return '';
            }

            $resolvedValue = $resolvedValue[$pathPart];
        }

        if (is_scalar($resolvedValue)) {
            return (string)$resolvedValue;
        }

        return '';
    }

    private function applyDefaultProperties(BaseType $schema, string $schemaKey, Site $site): void
    {
        $schemaBaseUrl = Util::buildSchemaBaseUrl($site);

        if ($schemaBaseUrl === '') {
            return;
        }

        if ($schema->getProperty('@id', '') === '') {
            $schema->setProperty('@id', $schemaBaseUrl . '/#' . $schemaKey);
        }
    }
}
