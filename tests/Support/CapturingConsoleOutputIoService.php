<?php

declare(strict_types=1);

namespace Tests\Support;

use DeployCore\Services\IoService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CapturingConsoleOutputIoService extends IoService
{
    /** @var list<string> */
    public array $lines = [];

    public function __construct()
    {
    }

    #[\Override]
    public function initialize(Command $command, InputInterface $input, OutputInterface $output): void
    {
    }

    #[\Override]
    public function out(string|iterable $lines, bool $force = false): void
    {
        foreach (is_string($lines) ? [$lines] : $lines as $line) {
            $this->lines[] = $line;
        }
    }

    #[\Override]
    public function write(string|iterable $messages, bool $newline = false, bool $force = false): void
    {
        foreach (is_string($messages) ? [$messages] : $messages as $message) {
            $this->lines[] = $message;
        }
    }

    #[\Override]
    public function writeln(string|iterable $lines, bool $force = false): void
    {
        $this->write($lines, true, $force);
    }
}
