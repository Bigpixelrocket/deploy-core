---
name: deployerphp-observer
description: Read-only DeployerPHP observer for infrastructure triage. Use when an agent must inspect inventory, server metadata, and logs without making changes. Supports only non-interactive read commands (`server:info`, `server:logs`) with explicit CLI options.
---

# DeployerPHP (Observer Tier)

Use this skill to inspect state only. Do not modify infrastructure.

## Execution Protocol

1. Run commands in non-interactive form with explicit options.
2. Read inventory before interpreting server state.
3. Use only read-only commands in this tier.
4. If mutation is required, stop and ask the user to run an admin-tier workflow.

## Required Context

Set concrete values before running commands:

```bash
PROJECT_ROOT="/path/to/project"
ENV_FILE="$PROJECT_ROOT/.env"
INVENTORY_FILE="$PROJECT_ROOT/.deployer/inventory.yml"
SERVER="production"
SITE="example.com"
```

## References

- Inventory schema: `.deployer/inventory.yml`
- Command catalog: `deployer list --raw`
- Command reference: `deployer help server:info`
- Command reference: `deployer help server:logs`

## Non-Interactive Command Reference

| Command                         | Reference                   | Complete non-interactive example                                                                                                                      |
| ------------------------------- | --------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| `server:info`                   | `deployer help server:info` | `deployer server:info --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                                                             |
| `server:logs` (system/services) | `deployer help server:logs` | `deployer server:logs --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --service="system,nginx,php8.3-fpm" --lines=200`             |
| `server:logs` (site scope)      | `deployer help server:logs` | `deployer server:logs --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --site="$SITE" --lines=200`                                  |
| `server:logs` (aggregate)       | `deployer help server:logs` | `deployer server:logs --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --service="all-sites,all-crons,all-supervisors" --lines=200` |

## Log Source Reference

Use these values with `--service` on `server:logs`:

- `system`
- `nginx`
- `php8.3-fpm` (or installed PHP-FPM service name)
- `mariadb`, `postgresql`
- `redis`, `memcached`
- `supervisor`
- `cron`
- `<domain>`
- `cron:<domain>/<script>`
- `supervisor:<domain>/<program>`
- `all-sites`
- `all-crons`
- `all-supervisors`

## Forbidden Commands In Observer Tier

- `server:run`
- All provisioning, deployment, lifecycle, and delete commands
- `server:ssh` and `site:ssh`

## Standard Observation Workflow

1. Read `.deployer/inventory.yml`.
2. Run `server:info` for target server.
3. Run `server:logs` with focused `--service` or `--site` filters.
4. Return findings and recommended next command for a human/admin-tier agent.
