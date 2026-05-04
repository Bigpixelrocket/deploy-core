<?php

declare(strict_types=1);

use DeployCore\Container;
use DeployCore\Contracts\BaseCommand;
use DeployCore\Services\IoService;
use DeployCore\SymfonyApp;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
        ->and($display)->toContain('⬢')
        ->and($display)->not->toContain('Ver:')
        ->and($display)->toContain('Available commands:');
});

it('uses Symfony version output for the version option', function (string $option): void {
    $tester = new ApplicationTester(makeConsoleOutputApplication());

    $status = $tester->run([$option => true], ['decorated' => false]);
    $display = $tester->getDisplay();

    expect($status)->toBe(Command::SUCCESS)
        ->and($display)->toMatch('/^DeployCore dev-[^\s]+$/')
        ->and($display)->not->toContain('Ver:')
        ->and($display)->not->toContain('Available commands:');
})->with(['--version', '-V']);

it('shows env and inventory details in command output', function (): void {
    withConsoleOutputWorkingDirectory(function (): void {
        $io = runDiagnosticCommandForConsoleOutput();
        $display = implode("\n", $io->lines);

        expect($display)
            ->toContain('Env:')
            ->toContain('Inv:');
    });
});

function makeConsoleOutputApplication(): SymfonyApp
{
    $app = (new Container())->build(SymfonyApp::class);
    $app->setAutoExit(false);

    return $app;
}

function runDiagnosticCommandForConsoleOutput(): CapturingConsoleOutputIoService
{
    $container = new Container();
    $io = new CapturingConsoleOutputIoService();
    $container->bind(IoService::class, $io);

    /** @var ConsoleOutputDiagnosticCommand $command */
    $command = $container->build(ConsoleOutputDiagnosticCommand::class);
    $tester = new CommandTester($command);
    $status = $tester->execute([], ['decorated' => false]);

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
        if (false === chdir($tempRoot)) {
            throw new RuntimeException(sprintf('Unable to switch to temp working directory: %s', $tempRoot));
        }

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
