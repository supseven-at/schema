<?php

declare(strict_types=1);

namespace Supseven\Schema\DataProcessing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionProperty;
use Supseven\Schema\Service\SchemaOrgService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(SchemaDataProcessor::class)]
class SchemaDataProcessorTest extends UnitTestCase
{
    private array $contentObjectConfiguration;
    private array $processorConfiguration;
    private array $processedData;
    private ServerRequestInterface&MockObject $request;
    private ContentObjectRenderer&MockObject $cObj;
    private SchemaOrgService&MockObject $schemaOrgService;
    private SchemaDataProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->getAlreadyProcessedProperty()->setValue(null, false);

        $this->contentObjectConfiguration = [];
        $this->processorConfiguration = [];
        $this->processedData = ['data' => ['foo' => 'bar']];

        $this->request = $this->createMock(ServerRequestInterface::class);

        $this->cObj = $this->createMock(ContentObjectRenderer::class);
        $this->cObj
            ->method($this->equalTo('getRequest'))
            ->willReturn($this->request);

        $this->schemaOrgService = $this->createMock(SchemaOrgService::class);

        $this->processor = new SchemaDataProcessor($this->schemaOrgService);
    }

    #[Test]
    public function processReturnsAmendedData(): void
    {
        $processedDataProcessed = $this->processedData['data'];
        $processedDataProcessed['_processedData'] = $this->processedData;

        $this->cObj
            ->expects($this->once())
            ->method($this->equalTo('stdWrapValue'))
            ->with($this->equalTo('singleProcessing'), $this->equalTo($this->processorConfiguration))
            ->willReturn(false);

        $this->schemaOrgService
            ->expects($this->once())
            ->method($this->equalTo('addStructuredData'))
            ->with($this->equalTo($processedDataProcessed), $this->equalTo($this->request));

        $result = $this->processor->process($this->cObj, $this->contentObjectConfiguration, $this->processorConfiguration, $this->processedData);

        $this->assertSame($this->processedData, $result);
    }

    #[Test]
    public function processWithSingleProcessingReturnsSameData(): void
    {
        $this->getAlreadyProcessedProperty()->setValue(null, true);

        $this->cObj
            ->expects($this->once())
            ->method($this->equalTo('stdWrapValue'))
            ->with($this->equalTo('singleProcessing'), $this->equalTo($this->processorConfiguration))
            ->willReturn(true);

        $this->schemaOrgService
            ->expects($this->never())
            ->method($this->equalTo('addStructuredData'));

        $result = $this->processor->process($this->cObj, $this->contentObjectConfiguration, $this->processorConfiguration, $this->processedData);

        $this->assertSame($this->processedData, $result);
        $this->assertTrue($this->getAlreadyProcessedProperty()->getValue());
    }

    #[Test]
    public function processWithSingleProcessingTwiceReturnsSameData(): void
    {
        $this->cObj
            ->expects($this->exactly(2))
            ->method($this->equalTo('stdWrapValue'))
            ->with($this->equalTo('singleProcessing'), $this->equalTo($this->processorConfiguration))
            ->willReturnOnConsecutiveCalls(true, true);

        $this->schemaOrgService
            ->expects($this->once())
            ->method($this->equalTo('addStructuredData'));

        // First call
        $result1 = $this->processor->process($this->cObj, $this->contentObjectConfiguration, $this->processorConfiguration, $this->processedData);
        $this->assertSame($this->processedData, $result1);

        // Second call - should return same data without processing
        $result2 = $this->processor->process($this->cObj, $this->contentObjectConfiguration, $this->processorConfiguration, $this->processedData);
        $this->assertSame($this->processedData, $result2);
    }

    private function getAlreadyProcessedProperty(): ReflectionProperty
    {
        $property = (new ReflectionClass(SchemaDataProcessor::class))->getProperty('alreadyProcessed');
        $property->setAccessible(true);

        return $property;
    }
}
