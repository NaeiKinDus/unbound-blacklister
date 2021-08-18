<?php

namespace Nullified\Formatters\Interfaces;

use Nullified\Providers\Interfaces\ProviderInterface;

interface FormatterInterface
{
    /**
     * Use parser to fetch adlists' content and format it as a data block that can be modified by the calling process
     * @param ProviderInterface $provider
     * @return \Generator
     */
    public function process(ProviderInterface $provider): \Generator;

    /**
     * Takes a data block generated by `process()` and writes it to $fileDest
     * @param mixed $dataBlock
     * @param bool $reuseDescriptor Whether to open a new file or reuse an existing fd
     */
    public function write($dataBlock, bool $reuseDescriptor = true): void;

    /**
     * Move the temporary file to its permanent destination and reset the object's state
     * @param string $fileDest
     */
    public function save(string $fileDest): void;
}