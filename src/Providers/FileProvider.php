<?php

namespace Nullified\Providers;

use Generator;
use Nullified\Providers\Interfaces\ProviderInterface;

class FileProvider implements Interfaces\ProviderInterface
{
    protected string $filepath;

    public function __construct(string $filepath)
    {
        throw new \UnexpectedValueException();
    }

    public function setParser(string $parser): self
    {
        // TODO: Implement setParser() method.
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function refresh(): void
    {
        // TODO: Implement refresh() method.
    }

    /**
     * {@inheritDoc}
     */
    public function getAdlists(): Generator
    {
        // TODO: Implement getAdlists() method.
        yield;
    }
}
