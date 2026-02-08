---
name: deployerphp-debugger
description: Read-focused DeployerPHP debugger for production triage. Use when an agent must inspect inventory, logs, service status, and filesystem/runtime state via non-interactive commands without changing infrastructure. Supports `server:info`, `server:logs`, and safe `server:run` diagnostics only.
---

# DeployerPHP (Debugger Tier)

Use this skill to diagnose problems without changing infrastructure.

## Execution Protocol

1. Run commands in non-interactive form with explicit options.
2. Read inventory before diagnostics.
3. Use only safe, read-only remote shell commands through `server:run`.
4. Never run interactive terminal commands (`less`, `top`, `vim`, `nano`, REPL tools).
5. If a fix requires mutation, stop and propose an admin-tier command.

## Required Context

Set concrete values before running commands:

```bash
PROJECT_ROOT="/path/to/project"
ENV_FILE="$PROJECT_ROOT/.env"
INVENTORY_FILE="$PROJECT_ROOT/.deployer/inventory.yml"
SERVER="production"
SITE="example.com"
PHP_SERVICE="php8.3-fpm"
```

## References

- Inventory schema: `.deployer/inventory.yml`
- Command catalog: `deployer list --raw`
- Command reference: `deployer help server:info`
- Command reference: `deployer help server:logs`
- Command reference: `deployer help server:run`

## Non-Interactive Command Reference

| Command                        | Reference                   | Complete non-interactive example                                                                                                     |
| ------------------------------ | --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| `server:info`                  | `deployer help server:info` | `deployer server:info --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                                            |
| `server:logs` (service scope)  | `deployer help server:logs` | `deployer server:logs --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --service="nginx,$PHP_SERVICE" --lines=200` |
| `server:logs` (site scope)     | `deployer help server:logs` | `deployer server:logs --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --site="$SITE" --lines=200`                 |
| `server:run` (command wrapper) | `deployer help server:run`  | `deployer server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="<SAFE_READ_ONLY_COMMAND>"`        |

## Safe `server:run` Command Reference

Run these exactly through `server:run`:

```bash
# Release + symlink inspection
deployer server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="ls -la /home/deployer/$SITE/releases"
deployer server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="readlink /home/deployer/$SITE/current"

# Service status (read-only)
deployer server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="systemctl status nginx --no-pager"
deployer server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="systemctl status $PHP_SERVICE --no-pager"

# Capacity + runtime
deployer server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="df -h"
deployer server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="free -h"
deployer server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="uptime"
deployer server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="ss -tuln"

# Logs + app files
deployer server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="tail -n 200 /home/deployer/$SITE/shared/storage/logs/laravel.log"
deployer server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="ls -la /home/deployer/$SITE/shared/.env"

# Process and PHP diagnostics
deployer server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="ps aux | grep php"
deployer server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="php -v"
deployer server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="php -i | grep memory_limit"
```

## Forbidden Commands In Debugger Tier

- Any command that changes state: deploy, install, create, delete, sync, start, stop, restart
- Destructive shell commands: `rm`, `mv`, `cp`, `chmod`, `chown`, `kill`, `reboot`, package managers
- Interactive terminal commands: `less`, `top`, `htop`, `vim`, `nano`, nested `ssh`

## Standard Debug Workflow

1. Read `.deployer/inventory.yml`.
2. Run `server:info`.
3. Run targeted `server:logs`.
4. Run safe `server:run` diagnostics.
5. Report root-cause hypothesis, evidence, and exact admin-tier command required for remediation.
