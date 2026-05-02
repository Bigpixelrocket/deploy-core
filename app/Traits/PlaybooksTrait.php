<?php

declare(strict_types=1);

namespace DeployCore\Traits;

use DeployCore\Container;
use DeployCore\DTOs\CronDTO;
use DeployCore\DTOs\ServerDTO;
use DeployCore\DTOs\SiteServerDTO;
use DeployCore\DTOs\SupervisorDTO;
use DeployCore\Exceptions\SshTimeoutException;
use DeployCore\Services\FilesystemService;
use DeployCore\Services\IoService;
use DeployCore\Services\SshService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Yaml\Yaml;

/**
 * Reusable playbook things.
 *
 * Requires classes using this trait to have Container, IoService, SshService, and FilesystemService properties.
 *
 * @property Container $container
 * @property FilesystemService $fs
 * @property IoService $io
 * @property SshService $ssh
 */
trait PlaybooksTrait
{
    // ----
    // Helpers
    // ----

    /**
     * Execute a playbook on a server.
     *
     * Handles SSH execution, error display, and YAML parsing.
     * Playbooks write YAML output to a temp file (DEPLOY_OUTPUT_FILE).
     * Displays errors via IoService and returns Command::FAILURE on any error.
     *
     * Standard playbook environment variables (auto-injected from context):
     *   - DEPLOY_OUTPUT_FILE: Output file path (always provided)
     *   - DEPLOY_SERVER_NAME: Server name - from server
     *   - DEPLOY_SSH_PORT: SSH port - from server
     *   - DEPLOY_PERMS: User permissions (root|sudo|none) - from server->info
     *   - DEPLOY_SITE_DOMAIN: Site domain - from site (when SiteServerDTO context)
     *   - DEPLOY_PHP_VERSION: PHP version - from site (when SiteServerDTO context)
     *   - DEPLOY_WEB_ROOT: Public web directory relative to current/ - from site (when SiteServerDTO context)
     *   - DEPLOY_SITE_REPO: Git repository URL - from site (when SiteServerDTO context and not null)
     *   - DEPLOY_SITE_BRANCH: Git branch - from site (when SiteServerDTO context and not null)
     *   - DEPLOY_CRONS: Cron jobs as JSON array - from site (when SiteServerDTO context)
     *   - DEPLOY_SUPERVISORS: Supervisor programs as JSON array - from site (when SiteServerDTO context)
     *
     * @param ServerDTO|SiteServerDTO $context Server or site+server context for playbook execution
     * @param string $playbookName Playbook name without .sh extension (e.g., 'server-info', 'php-install', etc)
     * @param string $statusMessage Message to display while executing the playbook
     * @param array<string, scalar|array<mixed>> $playbookVars Playbook variables (arrays are auto-encoded to JSON). Explicit vars override auto-injected ones.
     * @param string|null $capture Variable passed by reference to capture raw output. If null, output is streamed to console. If provided, output is captured silently.
     * @return array<string, mixed>|int Returns parsed YAML on success or Command::FAILURE on error
     */
    protected function executePlaybook(
        ServerDTO|SiteServerDTO $context,
        string $playbookName,
        string $statusMessage,
        array $playbookVars = [],
        ?string &$capture = null
    ): array|int {
        //
        // Extract context
        // ----

        $server = $context instanceof SiteServerDTO ? $context->server : $context;

        // Auto-inject server vars (always available)
        $baseVars = [
            'DEPLOY_SERVER_NAME' => $server->name,
            'DEPLOY_SSH_PORT' => (int) $server->port,
        ];

        // Auto-inject server info vars (when info has been gathered)
        if (null !== $server->info) {
            /** @var string $permissions */
            $permissions = $server->info['permissions'] ?? 'none';

            $baseVars['DEPLOY_PERMS'] = $permissions;
        }

        // Auto-inject site vars when SiteServerDTO context
        if ($context instanceof SiteServerDTO) {
            $site = $context->site;

            $baseVars['DEPLOY_SITE_DOMAIN'] = $site->domain;
            $baseVars['DEPLOY_PHP_VERSION'] = $site->phpVersion;
            $baseVars['DEPLOY_WEB_ROOT'] = $site->webRoot;

            if (null !== $site->repo && '' !== $site->repo) {
                $baseVars['DEPLOY_SITE_REPO'] = $site->repo;
            }

            if (null !== $site->branch && '' !== $site->branch) {
                $baseVars['DEPLOY_SITE_BRANCH'] = $site->branch;
            }

            $baseVars['DEPLOY_CRONS'] = array_map(
                fn (CronDTO $cron) => ['script' => $cron->script, 'schedule' => $cron->schedule],
                $site->crons
            );

            $baseVars['DEPLOY_SUPERVISORS'] = array_map(
                fn (SupervisorDTO $supervisor) => [
                    'program' => $supervisor->program,
                    'script' => $supervisor->script,
                    'autostart' => $supervisor->autostart,
                    'autorestart' => $supervisor->autorestart,
                    'stopwaitsecs' => $supervisor->stopwaitsecs,
                    'numprocs' => $supervisor->numprocs,
                ],
                $site->supervisors
            );
        }

        // Explicit vars override auto-injected defaults
        $playbookVars = [...$baseVars, ...$playbookVars];

        //
        // Prepare playbook

        $projectRoot = dirname(__DIR__, 2);
        $playbookPath = $projectRoot . '/playbooks/' . $playbookName . '.sh';
        $scriptContents = $this->fs->readFile($playbookPath);

        // Prepend helpers.sh content to playbook for remote execution
        $helpersPath = $projectRoot . '/playbooks/helpers.sh';
        if (file_exists($helpersPath)) {
            $helpersContents = $this->fs->readFile($helpersPath);
            $scriptContents = $helpersContents . "\n\n" . $scriptContents;
        }

        // Unique output file name
        $outputFile = sprintf('/tmp/deploy-core-output-%d-%s.yml', time(), bin2hex(random_bytes(8)));

        // Override default vars with playbook vars
        $vars = [
            'DEPLOY_OUTPUT_FILE' => $outputFile,
            ...$playbookVars,
        ];

        // Build var prefix string (arrays are auto-encoded to JSON)
        $varsPrefix = '';
        foreach ($vars as $key => $value) {
            $encoded = is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : (string) $value;
            $varsPrefix .= sprintf('%s=%s ', $key, escapeshellarg($encoded));
        }

        // Wrap script with environment and heredoc
        $scriptWithVars = sprintf(
            "%sbash <<'DEPLOY_SCRIPT_EOF'\n%s\nDEPLOY_SCRIPT_EOF",
            $varsPrefix,
            $scriptContents
        );

        //
        // Execution and output

        try {
            if (null === $capture) {
                // Streaming output in real time
                $this->out('$> ' . $statusMessage);

                $result = $this->ssh->executeCommand(
                    $server,
                    $scriptWithVars,
                    fn (string $chunk) => $this->io->write($chunk)
                );

                $this->out('───');
            } else {
                // No streaming, use spinner and capture output later
                $result = $this->io->promptSpin(
                    callback: fn () => $this->ssh->executeCommand(
                        $server,
                        $scriptWithVars
                    ),
                    message: $statusMessage
                );
            }

            // Display output when capturing only if there was an error
            if (null !== $capture && 0 !== $result['exit_code']) {
                $this->out('$>');
                $this->out(explode("\n", (string) $result['output']));
                $this->out('───');
            }

            $capture = trim((string) $result['output']);
        } catch (SshTimeoutException $e) {
            $this->nay($e->getMessage());
            $this->out([
                '',
                '<fg=yellow>The process took longer than expected to complete. Either:</>',
                '  • Server has a slow network connection',
                '  • Or the server is under heavy load',
                '',
                '<fg=yellow>You can try:</>',
                '  • Running the command again (operations are idempotent)',
                '  • Checking server load with <fg=cyan>server:info</>',
                '  • SSH into the server to check running processes',
                '',
            ]);

            return Command::FAILURE;
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        // Check exit code
        if ($result['exit_code'] !== 0) {
            $this->nay('Execution failed');

            return Command::FAILURE;
        }

        // Read YAML output from file and clean up (quick operation, short timeout)
        try {
            $yamlResult = $this->io->promptSpin(
                callback: fn () => $this->ssh->executeCommand(
                    $server,
                    sprintf('cat %s 2>/dev/null; rm -f %s 2>/dev/null', escapeshellarg($outputFile), escapeshellarg($outputFile)),
                    null,
                    30
                ),
                message: $statusMessage
            );

            $yamlContent = trim((string) $yamlResult['output']);

            if (empty($yamlContent)) {
                throw new \RuntimeException('Something went wrong while trying to read ' . $outputFile);
            }
        } catch (\RuntimeException $e) {
            $this->nay($e->getMessage());

            return Command::FAILURE;
        }

        // Parse YAML
        try {
            $parsed = Yaml::parse($yamlContent);

            if (!is_array($parsed)) {
                throw new \RuntimeException('Unexpected format, expected YAML array in ' . $outputFile);
            }

            /** @var array<string, mixed> $parsed */
            return $parsed;
        } catch (\Throwable $e) {
            $this->nay($e->getMessage());
            $this->out([
                '<fg=red>'.$yamlContent.'</>',
                '',
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Execute a playbook on a server silently.
     *
     * @param ServerDTO|SiteServerDTO $context Server or site+server context for playbook execution
     * @param string $playbookName Playbook name without .sh extension (e.g., 'server-info', 'php-install', etc)
     * @param string $spinnerMessage Message to display while executing the playbook
     * @param array<string, scalar|array<mixed>> $playbookVars Playbook variables (arrays are auto-encoded to JSON)
     * @return array<string, mixed>|int Returns parsed YAML on success or Command::FAILURE on error
     */
    protected function executePlaybookSilently(
        ServerDTO|SiteServerDTO $context,
        string $playbookName,
        string $spinnerMessage,
        array $playbookVars = [],
    ): array|int {
        $capture = '';

        return $this->executePlaybook(
            $context,
            $playbookName,
            $spinnerMessage,
            $playbookVars,
            $capture
        );
    }
}
