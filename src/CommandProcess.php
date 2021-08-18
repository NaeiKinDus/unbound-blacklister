<?php

namespace Nullified;

use Nullified\Formatters\UnboundFormatter;
use Nullified\Providers\UrlProvider;
use Nullified\Providers\FileProvider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandProcess
{
    public const ARG_BLACKLIST  = 'unbound_file';
    public const ARG_PROVIDERS  = 'providers_file';

    protected InputInterface    $input;
    protected OutputInterface   $output;
    protected string            $blacklistFile;
    protected string            $providersFile;

    public function __construct(InputInterface $input, OutputInterface $output) {
        $this->input = $input;
        $this->output = $output;

        $this->blacklistFile = $input->getArgument(self::ARG_BLACKLIST);
        $this->providersFile = $input->getArgument(self::ARG_PROVIDERS);
    }

    public function process(): void
    {
        if (substr($this->providersFile, 0, 4) == 'http') {
            $provider = new UrlProvider($this->providersFile);
        } elseif (file_exists($this->providersFile)) {
            $provider = new FileProvider($this->providersFile);
        } else {
            $this->output->writeln('<error>Unrecognized provider source given, only URL.</error>');
            exit(1);
        }
        $provider->refresh();

        $formatter = new UnboundFormatter();
        foreach ($formatter->process($provider) as $dataBlock) {
            $formatter->write($dataBlock);
        }
        $formatter->save($this->blacklistFile);

        // handle chown / chmod
        // handle error messages
    }
}