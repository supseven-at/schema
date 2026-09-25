<?php

declare(strict_types=1);

namespace Supseven\Schema\Utility;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(Util::class)]
class UtilTest extends UnitTestCase
{
    #[Test]
    #[DataProvider('durationProvider')]
    public function formatDurationFromSecondsReturnsExpectedResult(int $seconds, string $expectedDuration): void
    {
        $this->assertSame($expectedDuration, Util::formatDurationFromSeconds($seconds));
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function durationProvider(): array
    {
        return [
            '1 hour 30 minutes 45 seconds' => [5445, 'PT1H30M45S'],
            '2 hours'                      => [7200, 'PT2H'],
            '45 seconds'                   => [45, 'PT45S'],
            '1 minute'                     => [60, 'PT1M'],
            '0 seconds'                    => [0, ''],
            'negative seconds'             => [-1, ''],
        ];
    }

    #[Test]
    #[DataProvider('timestampProvider')]
    public function formatDateTimeFromTimestampReturnsExpectedResult(int $timestamp, ?string $expectedDateTime): void
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');

        try {
            $dateTime = Util::formatDateTimeFromTimestamp($timestamp);

            if ($expectedDateTime === null) {
                $this->assertNull($dateTime);

                return;
            }

            $this->assertInstanceOf(\DateTimeImmutable::class, $dateTime);
            $this->assertSame($expectedDateTime, $dateTime->format('Y-m-d H:i:s'));
        } finally {
            date_default_timezone_set($originalTimezone);
        }
    }

    /**
     * @return array<string, array{0: int, 1: string|null}>
     */
    public static function timestampProvider(): array
    {
        return [
            'valid timestamp'    => [1609459200, '2021-01-01 00:00:00'],
            'zero timestamp'     => [0, null],
            'negative timestamp' => [-1, null],
        ];
    }

    #[Test]
    #[DataProvider('absoluteUrlProvider')]
    public function getAbsoluteUrlReturnsExpectedResult(string $url, string $expectedUrl): void
    {
        $siteLanguage = $this->createMock(\TYPO3\CMS\Core\Site\Entity\SiteLanguage::class);
        $siteLanguage->method('getBase')->willReturn(new \TYPO3\CMS\Core\Http\Uri('https://example.com/'));

        $this->assertSame($expectedUrl, Util::getAbsoluteUrl($url, $siteLanguage));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function absoluteUrlProvider(): array
    {
        return [
            'relative path'           => ['/path/to/page', 'https://example.com/path/to/page'],
            'absolute url with host'  => ['https://other.com', 'https://other.com'],
            'empty url'               => ['', ''],
            'with query parameters'   => ['/path/to/page?param=value', 'https://example.com/path/to/page?param=value'],
            'with fragment'           => ['/path/to/page#section', 'https://example.com/path/to/page#section'],
            'with query and fragment' => ['/path/to/page?param=value#section', 'https://example.com/path/to/page?param=value#section'],
        ];
    }

    #[Test]
    public function buildProviderUrlReturnsUrlToProvider(): void
    {
        $pageUid = 42;
        $configuration = [
            'settings' => [
                'uids' => [
                    'organization' => $pageUid,
                ],
            ],
        ];

        $expectedUrl = 'https://example.com/organization';

        $uriInterface = $this->createMock(UriInterface::class);
        $uriInterface->method('__toString')->willReturn($expectedUrl);

        $routerInterface = $this->createMock(RouterInterface::class);
        $routerInterface
            ->expects($this->once())
            ->method($this->equalTo('generateUri'))
            ->with($this->equalTo($pageUid))
            ->willReturn($uriInterface);

        $site = $this->createMock(Site::class);
        $site
            ->expects($this->once())
            ->method($this->equalTo('getConfiguration'))
            ->willReturn($configuration);
        $site
            ->expects($this->once())
            ->method($this->equalTo('getRouter'))
            ->willReturn($routerInterface);

        $providerUrl = Util::buildProviderUrl($site);

        $this->assertSame($expectedUrl, $providerUrl);
    }
}
