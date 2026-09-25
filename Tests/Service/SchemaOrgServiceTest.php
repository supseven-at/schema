<?php

declare(strict_types=1);

namespace Supseven\Schema\Service;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use Spatie\SchemaOrg\BaseType;
use Spatie\SchemaOrg\Schema;
use Supseven\Schema\Builder\SchemaBuilderInterface;
use Supseven\Schema\SchemaContext;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SchemaOrgServiceTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Environment::initialize(
            new ApplicationContext('Testing'),
            true,
            false,
            sys_get_temp_dir(),
            sys_get_temp_dir(),
            sys_get_temp_dir(),
            sys_get_temp_dir(),
            'test',
            PHP_OS_FAMILY
        );
    }

    #[Test]
    public function itCanBeInstantiated(): void
    {
        $testBuilder = $this->createStubBuilder();
        $service = new SchemaOrgService(
            $this->createStub(PageRenderer::class),
            [$testBuilder]
        );

        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('builders');
        $property->setAccessible(true);
        $builders = $property->getValue($service);

        $this->assertInstanceOf(SchemaOrgService::class, $service);
        $this->assertCount(1, $builders);
        $this->assertSame($testBuilder, $builders[0]);
    }

    #[Test]
    public function noSiteObjectDoesNotCallBuildGraphOrInjectJsonLd(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getAttribute')
            ->with('site')
            ->willReturn(null);

        $pageRenderer = $this->createMock(PageRenderer::class);
        $pageRenderer
            ->expects($this->never())
            ->method('addHeaderData');

        $service = new SchemaOrgService($pageRenderer, []);
        $service->addStructuredData([], $request);
    }

    /**
     * Creates a minimal SchemaBuilderInterface stub for tests that only need
     * a generic builder to exercise SchemaOrgService's own dispatch logic.
     *
     * Concrete builder implementations (FaqSchemaBuilder, EventSchemaBuilder, etc.)
     * are project-specific and live in the consuming site extension (EXT:theme),
     * where they are also have to be tested.
     */
    private function createStubBuilder(): SchemaBuilderInterface
    {
        return new class () implements SchemaBuilderInterface {
            public function supports(mixed $item): bool
            {
                return true;
            }

            public function build(mixed $item, SchemaContext $context): BaseType
            {
                return Schema::thing()->name('Stub');
            }
        };
    }
}
