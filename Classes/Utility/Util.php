<?php

declare(strict_types=1);

namespace Supseven\Schema\Utility;

use DateTimeImmutable;
use DateTimeZone;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Utility class providing various helper methods for formatting, URL handling,
 * and text processing.
 */
class Util
{
    /**
     * Converts a duration in seconds into an ISO 8601 duration format string.
     *
     * The result is prefixed with 'PT' and includes hours ('H'), minutes ('M'), and
     * seconds ('S') components, as applicable. If the duration is less than or equal
     * to zero, an empty string is returned.
     *
     * @param int $durationInSeconds The duration in seconds to be converted.
     * @return string The formatted duration as an ISO 8601 string, or an empty string
     * if the input duration is less than or equal to zero.
     */
    public static function formatDurationFromSeconds(int $durationInSeconds): string
    {
        if ($durationInSeconds <= 0) {
            return '';
        }

        $hours = intdiv($durationInSeconds, 3600);
        $minutes = intdiv($durationInSeconds % 3600, 60);
        $seconds = $durationInSeconds % 60;

        $duration = 'PT';

        if ($hours > 0) {
            $duration .= $hours . 'H';
        }

        if ($minutes > 0) {
            $duration .= $minutes . 'M';
        }

        if ($seconds > 0 || $duration === 'PT') {
            $duration .= $seconds . 'S';
        }

        return $duration;
    }

    /**
     * Converts a given timestamp into a DateTimeImmutable instance, based on the default timezone.
     *
     * @param int $timestamp The Unix timestamp to be converted. Must be greater than 0.
     * @return DateTimeImmutable|null The resulting date-time, or null if the timestamp is invalid.
     */
    public static function formatDateTimeFromTimestamp(int $timestamp): ?DateTimeImmutable
    {
        if ($timestamp <= 0) {
            return null;
        }

        return (new DateTimeImmutable('@' . $timestamp))
            ->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }

    /**
     * Constructs an absolute URL based on the given relative or absolute URL and the site's language configuration.
     *
     * @param string $url The input URL, which can be either relative or absolute.
     * @param SiteLanguage $siteLanguage An instance of SiteLanguage providing the base URL configuration.
     * @return string Returns the absolute URL as a string. If the input URL is empty, an empty string is returned.
     */
    public static function getAbsoluteUrl(string $url, SiteLanguage $siteLanguage): string
    {
        if ($url === '') {
            return '';
        }

        $uri = new Uri($url);

        if ($uri->getHost() === '') {
            $url = $siteLanguage->getBase()->withPath($uri->getPath());

            if ($uri->getQuery() !== '') {
                $url = $url->withQuery($uri->getQuery());
            }

            if ($uri->getFragment() !== '') {
                $url = $url->withFragment($uri->getFragment());
            }
        }

        return (string)$url;
    }

    /**
     * Cleans the given text by removing HTML tags and replacing specific line break tags with a designated replacement string.
     *
     * @param string $text The input text containing potential HTML elements and line breaks to be cleaned.
     * @param string $replacement The string to replace line break tags with. Defaults to a single space if not provided.
     * @return string Returns the cleaned text with HTML tags removed and line breaks replaced by the specified replacement string.
     */
    public static function cleanText(string $text, string $replacement = ' '): string
    {
        return trim(strip_tags(
            str_replace(['<br>', '<br/>', '<br />'], $replacement, $text)
        ));
    }

    /**
     * Builds the base URL used for global Schema.org entity identifiers.
     *
     * @param Site $site The site for which to build the schema base URL.
     * @return string The normalized schema base URL without trailing slash.
     */
    public static function buildSchemaBaseUrl(Site $site): string
    {
        $settings = $site->getSettings()->getAll();
        $schemaUrl = $settings['schema']['shared']['url'] ?? '';

        if (is_string($schemaUrl) && trim($schemaUrl) !== '') {
            return rtrim(trim($schemaUrl), '/');
        }

        return rtrim((string)$site->getBase(), '/');
    }

    /**
     * Builds the provider URL for the given site.
     *
     * @param Site $site The site for which to build the provider URL.
     * @return string The provider URL.
     */
    public static function buildProviderUrl(Site $site): string
    {
        $siteConfiguration = $site->getConfiguration();

        return (string)$site->getRouter()->generateUri($siteConfiguration['settings']['uids']['organization']);
    }
}
