#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use Nullified\CommandProcess;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SingleCommandApplication;

try {
    $selfPhar = new Phar(Phar::running(false));
    $metadata = $selfPhar->getMetadata();
    $version = $metadata['version'] ?? 'unknown';
    $buildDate = $metadata['build_date'] ?? 'unknown';
} catch (Exception $exception) {
    // Most likely not in a phar archive
    $version = 'unknown';
    $buildDate = 'unknown';
}

$app = (new SingleCommandApplication())
    ->setName('unbound-blacklist')
    ->setVersion($version)
    ->addArgument(
            CommandProcess::ARG_PROVIDERS,
            InputArgument::REQUIRED,
            'Path or URL to a list (plain text file) of adlist providers'
    )
    ->addArgument(
            CommandProcess::ARG_BLACKLIST,
            InputArgument::OPTIONAL,
            'Path to the unbound file used for blacklisting',
            '/var/unbound/conf.d/blacklist.conf'
    )
    ->addOption(
            CommandProcess::OPT_OPTIMIZE,
            CommandProcess::OPT_OPTIMIZE_SHORT,
            InputOption::VALUE_NEGATABLE,
            'Deduplicate domains and optimize formatter output',
            true
    )
    ->setCode(
        fn(InputInterface $input, OutputInterface $output) => (new CommandProcess($input, $output))->process()
    )
    ->run()
;
