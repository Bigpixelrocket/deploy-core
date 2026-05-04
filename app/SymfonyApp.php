<?php

declare(strict_types=1);

namespace DeployCore;

use DeployCore\Services\CommandDiscoveryService;
use DeployCore\Services\VersionService;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The Symfony application entry point.
 */
final class SymfonyApp extends SymfonyApplication
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly Container $container,
        private readonly VersionService $versionService,
        private readonly CommandDiscoveryService $commandDiscovery,
    ) {
        $name = 'DeployCore';
        $version = $this->versionService->getVersion();
        parent::__construct($name, $version);

        $this->registerCommands();

        $this->setDefaultCommand('list');
    }

    //
    // Public
    // ----

    /**
     * Override default input definition to remove unwanted options.
     */
    #[\Override]
    protected function getDefaultInputDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputArgument('command', InputArgument::OPTIONAL, 'The command to execute'),
            new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display help for the given command. When no command is given display help for the list command'),
            new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Do not output any message (except errors)'),
            new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
            new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this application version'),
            new InputOption('--ansi', '', InputOption::VALUE_NEGATABLE, 'Force (or disable --no-ansi) ANSI output', null),
        ]);
    }

    /**
     * Override to hide default Symfony application name/version display.
     */
    #[\Override]
    public function getHelp(): string
    {
        return '';
    }

    /**
     * The main execution method in Symfony Console applications.
     */
    #[\Override]
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $displayVersion = $input->hasParameterOption(['--version', '-V'], true);
        $isQuiet = $input->hasParameterOption(['--quiet', '-q'], true);
        $skipBanner = $displayVersion || $isQuiet; // Skip the banner if --version is requested or in quiet mode

        if (!$skipBanner) {
            $this->displayBanner();
        }

        return parent::doRun($input, $output);
    }

    //
    // Private
    // ----

    /**
     * Display retro BBS-style ASCII art banner.
     */
    private function displayBanner(): void
    {
        $logo = '⬢';
        $brandName = 'DeployCore';
        $version = $this->getVersion();
        $prefixPlain = $logo.' '.$brandName.' '.$version;

        $targetWidth = 79;
        $fillLength = $targetWidth - mb_strlen($prefixPlain, 'UTF-8') - 4;
        $fillLength = max(0, $fillLength);

        $segment = intdiv($fillLength, 4);
        $remainder = $fillLength % 4;

        $colorFills = [
            '<fg=cyan>'.str_repeat('━', $segment + ($remainder > 0 ? 1 : 0)).'</>',
            '<fg=bright-blue>'.str_repeat('━', $segment + ($remainder > 1 ? 1 : 0)).'</>',
            '<fg=magenta>'.str_repeat('━', $segment + ($remainder > 2 ? 1 : 0)).'</>',
            '<fg=yellow>'.str_repeat('━', $segment).'</>',
        ];

        $bannerLine = '<fg=#5c5c5c>▒</> <fg=#5c5c5c>'.$logo.'</> <options=bold>'.$brandName.'</> ' . implode('', $colorFills) . ' ' . $version;

        $this->io->writeln([
            '',
            $bannerLine,
        ]);
    }

    /**
     * Register commands with auto-wired dependencies.
     */
    private function registerCommands(): void
    {
        $commandClasses = $this->commandDiscovery->discover();

        foreach ($commandClasses as $commandClass) {
            /** @var Command $commandInstance */
            $commandInstance = $this->container->build($commandClass);
            $this->addCommand($commandInstance);
        }
    }
}
