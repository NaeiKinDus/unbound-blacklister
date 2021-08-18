<?php

namespace Nullified\Providers;

use Generator;
use Nullified\Data\Adlist;
use Nullified\Parsers\TextParser;
use RuntimeException;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class UrlProvider implements Interfaces\ProviderInterface
{
     // URL for master text file serving as a repository for all configured adlists.
    protected string                $source;
    protected HttpClientInterface   $client;
    protected array                 $adlists = [];
    protected string                $parser;
    protected bool                  $hardFail = false;

    /**
     * @param string $url URL of the master file.
     */
    public function __construct(string $url, bool $hardFail = false, ?string $parser = null)
    {
        $this->source = $url;
        $this->client = new CurlHttpClient();
        $this->parser = $parser ?: TextParser::class;
        $this->hardFail = $hardFail;
    }

    /**
     * {@inheritDoc}
     */
    public function setParser(string $parser): self
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function refresh(): void
    {
        try {
            $content = $this->fetchFile($this->source);
        } catch (\Exception $excp) {
            throw new RuntimeException('Could not fetch master adlist', 0, $excp);
        }
        $this->adlists = preg_split('/[\r\n]+/', rtrim($content));
    }

    /**
     * {@inheritDoc}
     */
    public function getAdlists(): Generator
    {
        foreach ($this->adlists as $adlistUrl) {
            $adlistData = new Adlist();
            $adlistData->source = $adlistUrl;
            $adlistData->lastUpdate = new \DateTime();

            try {
                $content = $this->fetchFile($adlistUrl);
                $this->parser::parse($adlistData, $content);
            } catch (\Exception $excp) {
                // handle hard / soft fail
                throw $excp;
            }

            yield $adlistData;
        }
    }

    /**
     * @param string $fileUrl
     * @return string
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    protected function fetchFile(string $fileUrl): string
    {
        $code = 0;
        try {
            $response = $this->client->request('GET', $fileUrl);
            $code = $response->getStatusCode();
        } catch (\Exception $excp) {
            throw new RuntimeException('Could not fetch master adlist', $code, $excp);
        }

        if (($code < 200) || ($code >= 400)) {
            throw new RuntimeException('Could not fetch master adlist (code ' . $code . '), aborting.', $code);
        }

        return $response->getContent();
    }
}