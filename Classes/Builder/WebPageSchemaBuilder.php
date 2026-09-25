<?php

declare(strict_types=1);

namespace Supseven\Schema\Builder;

use Psr\Http\Message\UriInterface;
use Spatie\SchemaOrg\Schema;
use Spatie\SchemaOrg\WebPage;
use Supseven\Schema\SchemaContext;
use Supseven\Schema\Utility\Util;

readonly class WebPageSchemaBuilder implements SchemaBuilderInterface
{
    private const DOKTYPE_STANDARD_PAGE = '1';
    private const SCHEMA_TYPE_WEBPAGE = 'webpage';
    private const FIELD_DOKTYPE = 'doktype';
    private const FIELD_SCHEMA_TYPE = 'tx_schema_schema_type';

    private const SUPPORTED_DOKTYPES = [
        self::DOKTYPE_STANDARD_PAGE,
    ];

    private const SUPPORTED_SCHEMA_TYPES = [
        self::SCHEMA_TYPE_WEBPAGE,
    ];

    public function supports(mixed $item): bool
    {
        return is_array($item)
            && $this->hasSupportedValue($item, self::FIELD_DOKTYPE, self::SUPPORTED_DOKTYPES)
            && $this->hasSupportedValue($item, self::FIELD_SCHEMA_TYPE, self::SUPPORTED_SCHEMA_TYPES);
    }

    public function build(mixed $item, SchemaContext $context): ?WebPage
    {
        if (!$this->supports($item)) {
            return null;
        }

        $site = $context->getRequest()->getAttribute('site');

        if (!$site instanceof Site) {
            $site = $context->getSite();
        }

        $name = Util::cleanText((string)($item['seo_title'] ?? ''));

        if ($name === '') {
            $name = Util::cleanText((string)($item['title'] ?? ''));
        }

        if ($name === '') {
            return null;
        }

        $description = Util::cleanText((string)($item['description'] ?? ''));
        $schemaBaseUrl = Util::buildSchemaBaseUrl($site);
        $pageUrl = $this->getCurrentPageUrl($context);

        if ($pageUrl === '') {
            return null;
        }

        $webPage = Schema::webPage()
            ->setProperty('@id', $pageUrl . '#webpage')
            ->url($pageUrl)
            ->name($name)
            ->isPartOf(
                Schema::webSite()
                    ->setProperty('@id', $schemaBaseUrl . '/#website')
            )
            ->about(
                Schema::organization()
                    ->setProperty('@id', $schemaBaseUrl . '/#organization')
            );

        if ($description !== '') {
            $webPage->description($description);
        }

        return $webPage;
    }

    private function hasSupportedValue(array $pageData, string $fieldName, array $supportedValues): bool
    {
        return in_array((string)($pageData[$fieldName] ?? ''), $supportedValues, true);
    }

    private function getCurrentPageUrl(SchemaContext $context): string
    {
        $uri = $context->getRequest()->getUri();

        if ($uri instanceof UriInterface) {
            return (string)$uri->withQuery('')->withFragment('');
        }

        return '';
    }
}
