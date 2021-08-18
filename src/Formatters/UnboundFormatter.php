<?php

namespace Nullified\Formatters;

use Nullified\Data\Adlist;
use Nullified\Providers\Interfaces\ProviderInterface;


class UnboundFormatter implements Interfaces\FormatterInterface
{
    public const BLOCKLIST_IP = '0.0.0.0';
    // TTL used for A records, in seconds
    public const DEFAULT_RECORD_TTL = 7200;

    protected string $tempFileName;
    /** @var resource */
    protected $fileDescriptor;

    /**
     * {@inheritDoc}
     */
    public function process(ProviderInterface $provider): \Generator
    {
        /** @var Adlist $adlist */
        foreach ($provider->getAdlists() as $adlist) {
            $dataBlock = "\n# Generation date: {$adlist->lastUpdate->format(DATE_ATOM)}\n" .
                "# Source: $adlist->source\n";
            foreach ($adlist->blockList as $domain) {
                $domain = addslashes($domain);
                $dataBlock .=
                    "local-zone: \"$domain\" redirect\n" .
                    "local-data: \"$domain " . self::DEFAULT_RECORD_TTL . " IN A " . self::BLOCKLIST_IP . "\"\n";
            }
            yield $dataBlock;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function write($dataBlock, bool $reuseDescriptor = true): void
    {
        if (!$reuseDescriptor && is_resource($this->fileDescriptor)) {
            $this->reset();
        }
        if (!is_resource($this->fileDescriptor)) {
            $this->tempFileName = tempnam(sys_get_temp_dir(), 'unbound-');
            $this->fileDescriptor = fopen($this->tempFileName, 'w');
            $dataBlock = "server:\n" . $dataBlock;
        }

         if (fwrite($this->fileDescriptor, $dataBlock) === false) {
             throw new \RuntimeException('Could not write data to temporary file "' . $this->tempFileName . '"');
         }
    }

    /**
     * {@inheritDoc}
     */
    public function save(string $fileDest): void
    {
        if (is_resource($this->fileDescriptor)) {
            fclose($this->fileDescriptor);
        }

        if (!is_file($this->tempFileName)) {
            throw new \RuntimeException('Temporary source file "' . $this->tempFileName . '" does not exist');
        }

        if (!rename($this->tempFileName, $fileDest)) {
            throw new \RuntimeException(
                'Could not move temporary file "' . $this->tempFileName . '" to specified file "' . $fileDest . '"'
            );
        }
        $this->tempFileName = '';
    }

    public function __destruct()
    {
        $this->reset();
    }

    /**
     * Cleans up the file descriptor and removes the temp file if it exists
     */
    protected function reset(): void
    {
        if (is_resource($this->fileDescriptor)) {
            fclose($this->fileDescriptor);
        }
        if ($this->tempFileName) {
            @unlink($this->tempFileName);
            $this->tempFileName = null;
        }
    }
}