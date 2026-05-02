---
name: deploy-core-admin
description: Full-access DeployCore operator for provisioning, deployment, DNS, services, cron, supervisor, and debugging. Use when an agent must execute infrastructure changes through explicit non-interactive CLI commands with complete option values and safety confirmations.
---

# DeployCore (Admin Tier)

Use this skill for full lifecycle operations.

## Execution Protocol

1. Run commands in non-interactive form with explicit options.
2. Read `.deploy-core/inventory.yml` before changes.
3. Use `deploy help <command>` before first use in a session.
4. Include confirmation flags (`--yes`, `--force`) for destructive commands.
5. Do not use interactive SSH commands (`server:ssh`, `site:ssh`) from AI agents.

## Required Context

Set concrete values before running commands:

```bash
PROJECT_ROOT="/path/to/project"
ENV_FILE="$PROJECT_ROOT/.env"
INVENTORY_FILE="$PROJECT_ROOT/.deploy-core/inventory.yml"

SERVER="production"
HOST="203.0.113.50"
SSH_USER="root"
SSH_PORT="22"
SSH_PRIVATE_KEY="~/.ssh/id_rsa"
SSH_PUBLIC_KEY="~/.ssh/id_rsa.pub"

DOMAIN="example.com"
REPO="git@github.com:acme/app.git"
BRANCH="main"
PHP_VERSION="8.3"
WWW_MODE="redirect-to-root"
WEB_ROOT="public"

CRON_SCRIPT=".deploy-core/scripts/cron.sh"
CRON_SCHEDULE="*/5 * * * *"
SUPERVISOR_PROGRAM="queue-worker"
SUPERVISOR_SCRIPT=".deploy-core/scripts/supervisor.sh"

AWS_KEY_PAIR="deploy-core-key"
AWS_ZONE="example.com"
AWS_INSTANCE_TYPE="t3.micro"
AWS_IMAGE="ubuntu-24.04"
AWS_VPC="vpc-xxxxxxxx"
AWS_SUBNET="subnet-xxxxxxxx"

DO_REGION="nyc3"
DO_IMAGE="ubuntu-24-04-x64"
DO_SIZE="s-1vcpu-1gb"
DO_KEY_NAME="deploy-core-key"
DO_SSH_KEY_ID="123456"
DO_VPC_UUID="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
DO_ZONE="example.com"

CF_ZONE="example.com"
```

`CRON_SCRIPT` and `SUPERVISOR_SCRIPT` must be paths relative to project root.

## Global References

- Inventory: `.deploy-core/inventory.yml`
- Command catalog: `deploy list --raw`
- Per-command reference: `deploy help <command>`

## Scaffolding Commands

| Command            | Reference                        | Complete non-interactive example                                                                                                              |
| ------------------ | -------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| `scaffold:scripts` | `deploy help scaffold:scripts` | `deploy scaffold:scripts --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --destination="$PROJECT_ROOT" --force`                             |
| `scaffold:ai`      | `deploy help scaffold:ai`      | `deploy scaffold:ai --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --destination="$PROJECT_ROOT" --agent=".agents" --tier="admin" --force` |

## Server Commands

| Command           | Reference                       | Complete non-interactive example                                                                                                                                                                                                                                          |
| ----------------- | ------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `server:add`      | `deploy help server:add`      | `deploy server:add --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --name="$SERVER" --host="$HOST" --port="$SSH_PORT" --username="$SSH_USER" --private-key-path="$SSH_PRIVATE_KEY"`                                                                                     |
| `server:info`     | `deploy help server:info`     | `deploy server:info --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                                                                                                                                                                                 |
| `server:install`  | `deploy help server:install`  | `deploy server:install --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --generate-deploy-key --php-version="$PHP_VERSION" --php-default --php-extensions="bcmath,ctype,curl,dom,fileinfo,mbstring,openssl,pcntl,pdo,tokenizer,xml" --timezone="UTC"` |
| `server:firewall` | `deploy help server:firewall` | `deploy server:firewall --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --allow="22,80,443" --yes`                                                                                                                                                   |
| `server:logs`     | `deploy help server:logs`     | `deploy server:logs --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --service="system,nginx,php8.3-fpm" --lines=200`                                                                                                                                 |
| `server:run`      | `deploy help server:run`      | `deploy server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="systemctl status nginx --no-pager"`                                                                                                                                    |
| `server:delete`   | `deploy help server:delete`   | `deploy server:delete --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --force --yes --no-destroy-cloud`                                                                                                                                              |
| `server:ssh`      | `deploy help server:ssh`      | `Not supported for AI agents (interactive terminal). Use server:run.`                                                                                                                                                                                                     |

## Site Commands

| Command            | Reference                        | Complete non-interactive example                                                                                                                                                        |
| ------------------ | -------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `site:create`      | `deploy help site:create`      | `deploy site:create --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --domain="$DOMAIN" --server="$SERVER" --php-version="$PHP_VERSION" --www-mode="$WWW_MODE" --web-root="$WEB_ROOT"` |
| `site:deploy`      | `deploy help site:deploy`      | `deploy site:deploy --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --domain="$DOMAIN" --repo="$REPO" --branch="$BRANCH" --keep-releases=5 --yes --force`                             |
| `site:https`       | `deploy help site:https`       | `deploy site:https --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --domain="$DOMAIN"`                                                                                                |
| `site:dns:check`   | `deploy help site:dns:check`   | `deploy site:dns:check --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --domain="$DOMAIN"`                                                                                            |
| `site:shared:list` | `deploy help site:shared:list` | `deploy site:shared:list --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --domain="$DOMAIN"`                                                                                          |
| `site:shared:push` | `deploy help site:shared:push` | `deploy site:shared:push --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --domain="$DOMAIN" --local="$PROJECT_ROOT/.env.production" --remote=".env" --force --yes`                    |
| `site:shared:pull` | `deploy help site:shared:pull` | `deploy site:shared:pull --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --domain="$DOMAIN" --remote=".env" --local="$PROJECT_ROOT/.env.backup" --yes`                                |
| `site:rollback`    | `deploy help site:rollback`    | `deploy site:rollback --env="$ENV_FILE" --inventory="$INVENTORY_FILE"`                                                                                                                |
| `site:delete`      | `deploy help site:delete`      | `deploy site:delete --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --domain="$DOMAIN" --force --yes`                                                                                 |
| `site:ssh`         | `deploy help site:ssh`         | `Not supported for AI agents (interactive terminal). Use server:run.`                                                                                                                   |

## Service Install Commands

| Command              | Reference                          | Complete non-interactive example                                                                                                                                                  |
| -------------------- | ---------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `mariadb:install`    | `deploy help mariadb:install`    | `deploy mariadb:install --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --display-credentials --save-credentials="$PROJECT_ROOT/.secrets/mariadb.txt"`       |
| `postgresql:install` | `deploy help postgresql:install` | `deploy postgresql:install --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --display-credentials --save-credentials="$PROJECT_ROOT/.secrets/postgresql.txt"` |
| `redis:install`      | `deploy help redis:install`      | `deploy redis:install --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --display-credentials --save-credentials="$PROJECT_ROOT/.secrets/redis.txt"`           |
| `memcached:install`  | `deploy help memcached:install`  | `deploy memcached:install --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                                                                                   |

## Service Lifecycle Commands

| Command              | Reference                          | Complete non-interactive example                                                                                       |
| -------------------- | ---------------------------------- | ---------------------------------------------------------------------------------------------------------------------- |
| `nginx:start`        | `deploy help nginx:start`        | `deploy nginx:start --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                              |
| `nginx:stop`         | `deploy help nginx:stop`         | `deploy nginx:stop --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                               |
| `nginx:restart`      | `deploy help nginx:restart`      | `deploy nginx:restart --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                            |
| `php:start`          | `deploy help php:start`          | `deploy php:start --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --php-version="$PHP_VERSION"`   |
| `php:stop`           | `deploy help php:stop`           | `deploy php:stop --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --php-version="$PHP_VERSION"`    |
| `php:restart`        | `deploy help php:restart`        | `deploy php:restart --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --php-version="$PHP_VERSION"` |
| `mariadb:start`      | `deploy help mariadb:start`      | `deploy mariadb:start --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                            |
| `mariadb:stop`       | `deploy help mariadb:stop`       | `deploy mariadb:stop --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                             |
| `mariadb:restart`    | `deploy help mariadb:restart`    | `deploy mariadb:restart --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                          |
| `postgresql:start`   | `deploy help postgresql:start`   | `deploy postgresql:start --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                         |
| `postgresql:stop`    | `deploy help postgresql:stop`    | `deploy postgresql:stop --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                          |
| `postgresql:restart` | `deploy help postgresql:restart` | `deploy postgresql:restart --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                       |
| `redis:start`        | `deploy help redis:start`        | `deploy redis:start --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                              |
| `redis:stop`         | `deploy help redis:stop`         | `deploy redis:stop --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                               |
| `redis:restart`      | `deploy help redis:restart`      | `deploy redis:restart --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                            |
| `memcached:start`    | `deploy help memcached:start`    | `deploy memcached:start --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                          |
| `memcached:stop`     | `deploy help memcached:stop`     | `deploy memcached:stop --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                           |
| `memcached:restart`  | `deploy help memcached:restart`  | `deploy memcached:restart --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                        |
| `supervisor:start`   | `deploy help supervisor:start`   | `deploy supervisor:start --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                         |
| `supervisor:stop`    | `deploy help supervisor:stop`    | `deploy supervisor:stop --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                          |
| `supervisor:restart` | `deploy help supervisor:restart` | `deploy supervisor:restart --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER"`                       |

## Cron Commands

| Command       | Reference                   | Complete non-interactive example                                                                                                              |
| ------------- | --------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| `cron:create` | `deploy help cron:create` | `deploy cron:create --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --domain="$DOMAIN" --script="$CRON_SCRIPT" --schedule="$CRON_SCHEDULE"` |
| `cron:delete` | `deploy help cron:delete` | `deploy cron:delete --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --domain="$DOMAIN" --script="$CRON_SCRIPT" --force --yes`               |
| `cron:sync`   | `deploy help cron:sync`   | `deploy cron:sync --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --domain="$DOMAIN"`                                                       |

## Supervisor Program Commands

| Command             | Reference                         | Complete non-interactive example                                                                                                                                                                                         |
| ------------------- | --------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `supervisor:create` | `deploy help supervisor:create` | `deploy supervisor:create --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --domain="$DOMAIN" --program="$SUPERVISOR_PROGRAM" --script="$SUPERVISOR_SCRIPT" --autostart --autorestart --stopwaitsecs=3600 --numprocs=1` |
| `supervisor:delete` | `deploy help supervisor:delete` | `deploy supervisor:delete --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --domain="$DOMAIN" --program="$SUPERVISOR_PROGRAM" --force --yes`                                                                            |
| `supervisor:sync`   | `deploy help supervisor:sync`   | `deploy supervisor:sync --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --domain="$DOMAIN"`                                                                                                                            |

## AWS Commands

| Command          | Reference                      | Complete non-interactive example                                                                                                                                                                                                                                                            |
| ---------------- | ------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `aws:key:add`    | `deploy help aws:key:add`    | `deploy aws:key:add --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --name="$AWS_KEY_PAIR" --public-key-path="$SSH_PUBLIC_KEY"`                                                                                                                                                           |
| `aws:key:list`   | `deploy help aws:key:list`   | `deploy aws:key:list --env="$ENV_FILE" --inventory="$INVENTORY_FILE"`                                                                                                                                                                                                                     |
| `aws:key:delete` | `deploy help aws:key:delete` | `deploy aws:key:delete --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --key="$AWS_KEY_PAIR" --force --yes`                                                                                                                                                                               |
| `aws:provision`  | `deploy help aws:provision`  | `deploy aws:provision --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --name="$SERVER" --instance-type="$AWS_INSTANCE_TYPE" --image="$AWS_IMAGE" --key-pair="$AWS_KEY_PAIR" --private-key-path="$SSH_PRIVATE_KEY" --vpc="$AWS_VPC" --subnet="$AWS_SUBNET" --disk-size=20 --no-monitoring` |
| `aws:dns:set`    | `deploy help aws:dns:set`    | `deploy aws:dns:set --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --zone="$AWS_ZONE" --type="A" --name="@" --value="$HOST" --ttl=300`                                                                                                                                                   |
| `aws:dns:list`   | `deploy help aws:dns:list`   | `deploy aws:dns:list --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --zone="$AWS_ZONE" --type="A"`                                                                                                                                                                                       |
| `aws:dns:delete` | `deploy help aws:dns:delete` | `deploy aws:dns:delete --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --zone="$AWS_ZONE" --type="A" --name="@" --force --yes`                                                                                                                                                            |

## DigitalOcean Commands

| Command         | Reference                     | Complete non-interactive example                                                                                                                                                                                                                                                    |
| --------------- | ----------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `do:key:add`    | `deploy help do:key:add`    | `deploy do:key:add --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --name="$DO_KEY_NAME" --public-key-path="$SSH_PUBLIC_KEY"`                                                                                                                                                     |
| `do:key:list`   | `deploy help do:key:list`   | `deploy do:key:list --env="$ENV_FILE" --inventory="$INVENTORY_FILE"`                                                                                                                                                                                                              |
| `do:key:delete` | `deploy help do:key:delete` | `deploy do:key:delete --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --key="$DO_SSH_KEY_ID" --force --yes`                                                                                                                                                                       |
| `do:provision`  | `deploy help do:provision`  | `deploy do:provision --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --name="$SERVER" --region="$DO_REGION" --image="$DO_IMAGE" --private-key-path="$SSH_PRIVATE_KEY" --size="$DO_SIZE" --ssh-key-id="$DO_SSH_KEY_ID" --vpc-uuid="$DO_VPC_UUID" --no-backups --ipv6 --monitoring` |
| `do:dns:set`    | `deploy help do:dns:set`    | `deploy do:dns:set --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --zone="$DO_ZONE" --type="A" --name="@" --value="$HOST" --ttl=300`                                                                                                                                             |
| `do:dns:list`   | `deploy help do:dns:list`   | `deploy do:dns:list --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --zone="$DO_ZONE" --type="A"`                                                                                                                                                                                 |
| `do:dns:delete` | `deploy help do:dns:delete` | `deploy do:dns:delete --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --zone="$DO_ZONE" --type="A" --name="@" --force --yes`                                                                                                                                                      |

## Cloudflare DNS Commands

| Command         | Reference                     | Complete non-interactive example                                                                                                                  |
| --------------- | ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| `cf:dns:set`    | `deploy help cf:dns:set`    | `deploy cf:dns:set --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --zone="$CF_ZONE" --type="A" --name="@" --value="$HOST" --ttl=300 --proxied` |
| `cf:dns:list`   | `deploy help cf:dns:list`   | `deploy cf:dns:list --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --zone="$CF_ZONE" --type="A"`                                               |
| `cf:dns:delete` | `deploy help cf:dns:delete` | `deploy cf:dns:delete --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --zone="$CF_ZONE" --type="A" --name="@" --force --yes`                    |

## Safe Diagnostic Commands In Admin Tier

Use `server:run` for diagnostics while staying non-interactive:

```bash
deploy server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="systemctl status nginx --no-pager"
deploy server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="df -h"
deploy server:run --env="$ENV_FILE" --inventory="$INVENTORY_FILE" --server="$SERVER" --command="tail -n 200 /home/deployer/$DOMAIN/shared/storage/logs/laravel.log"
```

## Suggested Execution Order (Greenfield)

1. `scaffold:scripts`
2. `server:add`
3. `server:install`
4. `site:create`
5. `site:deploy`
6. `site:https`
7. `site:shared:push`
8. `cron:create` + `cron:sync` (if needed)
9. `supervisor:create` + `supervisor:sync` (if needed)
