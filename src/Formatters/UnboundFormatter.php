<?php

namespace Nullified\Formatters;

use Nullified\Data\Adlist;
use Nullified\Providers\Interfaces\ProviderInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;


class UnboundFormatter implements Interfaces\FormatterInterface
{
    public const BLOCKLIST_IP = '0.0.0.0';
    // TTL used for A records, in seconds
    public const DEFAULT_RECORD_TTL = 7200;

    protected ?string           $tempFileName;
    /** @var resource */
    protected                   $fileDescriptor;
    protected OutputInterface   $output;
    protected bool              $progress;

    /**
     * First bar for ad list processing;
     * second bar for domain processing in each ad list.
     * @var array<ConsoleSectionOutput>
     */
    protected array             $outputSections;
    /**
     * First bar for ad list processing;
     * second bar for domain processing in each ad list.
     * @var array<ProgressBar>
     */
    protected array             $progressBars;

    public function __construct(OutputInterface $output, bool $progress = false)
    {
        $this->output = $output;
        $this->progress = $progress;

        if ($progress) {
            $this->outputSections = [
                $output->section(),
                $output->section()
            ];
            $this->progressBars = [
                new ProgressBar($this->outputSections[0]),
                new ProgressBar($this->outputSections[1])
            ];

            $this->progressBars[0]->setMessage('adlists');
            $this->progressBars[0]->setFormat(" %current:6s%/%max:6s% %message:14s% [%bar%] %percent:3s%% %elapsed:6s% %memory%");
            $this->progressBars[1]->setMessage('adlist domains');
            $this->progressBars[1]->setFormat(" %current:6s%/%max:6s% %message:14s% [%bar%] %percent:3s%% %elapsed:6s% %memory%");
        }
    }

    public function __destruct()
    {
        $this->reset();
    }

    /**
     * {@inheritDoc}
     */
    public function process(ProviderInterface $provider, bool $deduplicate = true): \Generator
    {
        $processedDomains = [];

        if ($this->progress) {
            $this->progressBars[0]->start($provider->getAdlistCount());
        }

        /** @var Adlist $adlist */
        foreach ($provider->getAdlists() as $adlist) {
            $dataBlock = '';
            if ($adlist->lastUpdate) {
                $dataBlock .= "\n# Generation date: {$adlist->lastUpdate->format(DATE_ATOM)}\n";
            }
            $dataBlock .= "# Source: $adlist->source\n";

            if ($this->progress) {
                $this->progressBars[1]->start($adlist->size);
            }
            foreach ($adlist->blockList as $domain) {
                $domain = addslashes(strtolower($domain));
                if (!$deduplicate || ($deduplicate && empty($processedDomains[$domain]))) {
                    $dataBlock .=
                        "local-zone: \"$domain\" redirect\n" .
                        "local-data: \"$domain " . self::DEFAULT_RECORD_TTL . " IN A " . self::BLOCKLIST_IP . "\"\n";
                    if ($deduplicate) {
                        $processedDomains[$domain] = 1;
                    }
                }
                if ($this->progress) {
                    $this->progressBars[1]->advance();
                }
            }
            if ($this->progress) {
                $this->progressBars[1]->finish();
                $this->progressBars[0]->advance();
            }
            yield $dataBlock;
        }

        if ($this->progress) {
            $this->progressBars[0]->finish();
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
            $tmpDir = sys_get_temp_dir();
            $this->tempFileName = tempnam($tmpDir, 'unbound-');
            if ($this->tempFileName === false) {
                throw new \RuntimeException('Could not generate a new temporary file in temporary directory "' . $tmpDir . '"');
            }
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

    /**
     * Cleans up the file descriptor and removes the temp file if it exists
     */
    protected function reset(): void
    {
        if (is_resource($this->fileDescriptor)) {
            fclose($this->fileDescriptor);
        }
        if (!empty($this->tempFileName)) {
            @unlink($this->tempFileName);
            $this->tempFileName = null;
        }
    }
}