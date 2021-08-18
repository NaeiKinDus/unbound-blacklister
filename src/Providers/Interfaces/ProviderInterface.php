<?php

namespace Nullified\Providers\Interfaces;

use Generator;
use Nullified\Data\Adlist;

interface ProviderInterface
{
    /**
     * Refreshes remote master file content
     * @return void
     */
    public function refresh(): void;

    /**
     * @param string $parser The class of a parser to be used with adlists
     * @return $this
     */
    public function setParser(string $parser): self;

    /**
     * Returns a list of Adlist objects
     * @return Generator<Adlist>
     */
    public function getAdlists(): Generator;
}