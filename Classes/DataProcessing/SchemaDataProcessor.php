<?php

declare(strict_types=1);

namespace Supseven\Schema\DataProcessing;

use Supseven\Schema\Service\SchemaOrgService;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Schema.org DataProcessor
 *
 * Generates JSON-LD schema for specific CTypes
 */
#[Autoconfigure(public: true)]
#[AutoconfigureTag('data.processor', ['identifier' => 'schema-processor'])]
class SchemaDataProcessor implements DataProcessorInterface
{
    private static bool $alreadyProcessed = false;

    public function __construct(
        private readonly SchemaOrgService $schemaOrgService
    ) {
    }

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $isSingleProcessing = (bool)$cObj->stdWrapValue('singleProcessing', $processorConfiguration);

        if ($isSingleProcessing && self::$alreadyProcessed) {
            return $processedData;
        }

        self::$alreadyProcessed = true;

        $data = $processedData['data'] ?? $cObj->data;
        $data['_processedData'] = $processedData;

        $this->schemaOrgService->addStructuredData($data, $cObj->getRequest());

        return $processedData;
    }
}
