<?php

declare(strict_types=1);

use DeployCore\Container;
use DeployCore\Contracts\BaseCommand;
use DeployCore\Services\IoService;
use DeployCore\SymfonyApp;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Support\CapturingConsoleOutputIoService;

it('shows the banner without version details for normal application output', function (): void {
    $tester = new ApplicationTester(makeConsoleOutputApplication());

    $status = $tester->run(['command' => 'list'], ['decorated' => false]);
    $display = $tester->getDisplay();

    expect($status)->toBe(Command::SUCCESS)
        ->and($display)->toContain('DeployCore')
        ->and($display)->not->toContain('Ver:')
        ->and($display)->toContain('Available commands:');
});

it('uses Symfony version output for the version option', function (string $option): void {
    $tester = new ApplicationTester(makeConsoleOutputApplication());

    $status = $tester->run([$option => true], ['decorated' => false]);
    $display = $tester->getDisplay();

    expect($status)->toBe(Command::SUCCESS)
        ->and($display)->toContain('DeployCore dev-main')
        ->and($display)->not->toContain('Ver:')
        ->and($display)->not->toContain('Available commands:');
})->with(['--version', '-V']);

it('defines the standard global verbose option shortcuts', function (): void {
    $option = makeConsoleOutputApplication()->getDefinition()->getOption('verbose');

    expect($option)->toBeInstanceOf(InputOption::class)
        ->and($option->getShortcut())->toBe('v|vv|vvv')
        ->and($option->acceptValue())->toBeFalse();
});

it('shows env and inventory details at normal and verbose command output levels', function (): void {
    withConsoleOutputWorkingDirectory(function (): void {
        $normalIo = runDiagnosticCommandForConsoleOutput(OutputInterface::VERBOSITY_NORMAL);
        $normalDisplay = implode("\n", $normalIo->lines);

        expect($normalDisplay)
            ->toContain('Env:')
            ->toContain('Inv:');

        foreach ([
            OutputInterface::VERBOSITY_VERBOSE,
            OutputInterface::VERBOSITY_VERY_VERBOSE,
            OutputInterface::VERBOSITY_DEBUG,
        ] as $verbosity) {
            $verboseIo = runDiagnosticCommandForConsoleOutput($verbosity);
            $display = implode("\n", $verboseIo->lines);

            expect($display)
                ->toContain('Env:')
                ->toContain('Inv:');
        }
    });
});

function makeConsoleOutputApplication(): SymfonyApp
{
    $app = (new Container())->build(SymfonyApp::class);
    $app->setAutoExit(false);

    return $app;
}

function runDiagnosticCommandForConsoleOutput(int $verbosity): CapturingConsoleOutputIoService
{
    $container = new Container();
    $io = new CapturingConsoleOutputIoService();
    $container->bind(IoService::class, $io);

    /** @var ConsoleOutputDiagnosticCommand $command */
    $command = $container->build(ConsoleOutputDiagnosticCommand::class);
    $tester = new CommandTester($command);
    $status = $tester->execute([], ['decorated' => false, 'verbosity' => $verbosity]);

    expect($status)->toBe(Command::SUCCESS);

    return $io;
}

function withConsoleOutputWorkingDirectory(callable $callback): void
{
    $filesystem = new Filesystem();
    $tempRoot = sys_get_temp_dir() . '/deploy-core-console-output-test-' . bin2hex(random_bytes(6));
    $originalCwd = getcwd();

    if (false === $originalCwd) {
        throw new RuntimeException('Unable to determine current working directory');
    }

    $filesystem->mkdir($tempRoot);

    try {
        chdir($tempRoot);
        $callback();
    } finally {
        chdir($originalCwd);
        $filesystem->remove($tempRoot);
    }
}

#[AsCommand(name: 'console-output:diagnostic', description: 'Test command for console output diagnostics')]
final class ConsoleOutputDiagnosticCommand extends BaseCommand
{
}
