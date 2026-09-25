<?php

declare(strict_types=1);

namespace Supseven\Schema;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Class SchemaContext
 *
 * An immutable data transfer object (DTO) that holds request-specific and site-specific
 * parameters required during the Schema.org structured data generation.
 * This prevents storing request-specific state in singleton services.
 */
class SchemaContext
{
    /**
     * @var string The ISO 3166-1 alpha-2 country code (e.g., 'AT', 'DE', 'CH') parsed from site settings.
     */
    private string $country;

    /**
     * @var string The normalized base URL scheme and host (e.g., 'https://example.com').
     */
    private string $host;

    /**
     * SchemaContext constructor.
     *
     * @param ServerRequestInterface $request The current PSR-7 server request.
     * @param Site $site The current TYPO3 site entity.
     */
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly Site $site
    ) {
        $this->country = $this->site->getConfiguration()['settings']['site']['country'] ?? 'AT';
        $this->host = $this->site->getBase()->getScheme() . '://' . $this->site->getBase()->getHost();
    }

    /**
     * Gets the current PSR-7 server request.
     *
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Gets the current TYPO3 site entity.
     *
     * @return Site
     */
    public function getSite(): Site
    {
        return $this->site;
    }

    /**
     * Gets the resolved country configuration.
     *
     * @return string ISO country code.
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Gets the resolved base host URL (without trailing slash).
     *
     * @return string The base URL host.
     */
    public function getHost(): string
    {
        return $this->host;
    }
}
