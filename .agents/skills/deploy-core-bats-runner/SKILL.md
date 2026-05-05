---
name: deploy-core-bats-runner
description: Run DeployCore BATS integration suites locally or through GitHub Actions manual dispatch. Use when the user asks to run, trigger, monitor, inspect, or explain VM BATS, cloud BATS, manual BATS workflow runs, branch-selected BATS runs, or BATS cleanup. Do not use for editing BATS test code; use deployerphp-bats-testing for test implementation changes.
---

# BATS Runner

Use this skill when operating the existing BATS suite. The BATS suite is expensive enough that agents must distinguish normal PHP tests from VM and cloud integration tests.

> **IMPORTANT**
>
> - Cloud BATS creates real cloud resources and costs money. Run it only when the user explicitly asks for cloud BATS in the current task, or after confirming the target provider/suite.
> - Prefer GitHub Actions manual dispatch for shared branch validation, because the workflow captures logs, branch, inputs, secrets, cleanup, and run status in one place.
> - Prefer local `./bats.sh` only when the user asks for local execution or the task needs fast iteration on this machine.

## Context

Primary files:

- `.github/workflows/test.yml` defines the `Test` workflow and `workflow_dispatch` input `bats_suite`.
- `bats.sh` is the only supported local BATS entry point.
- `tests/bats/lib/cloud-janitor.sh` is the cloud cleanup backstop.

Manual workflow input values:

- `none`: PHP test matrix only.
- `vm`: PHP test matrix, then VM BATS.
- `cloud`: PHP test matrix, then cloud BATS.
- `all`: PHP test matrix, VM BATS, then cloud BATS if VM passes.

## Protocol

1. Identify the requested suite: `vm`, `cloud`, `all`, cleanup, or status/log inspection.
2. Identify the target branch or ref. Use the current branch when the user does not specify one.
3. For cloud BATS, confirm cost intent unless the user already made the cloud run explicit.
4. Prefer GitHub Actions dispatch when branch selection, CI secrets, or shared evidence matters.
5. Monitor the run when requested or when you trigger it yourself; report the run URL, status, failed job, and the shortest useful failure excerpt.
6. If cloud BATS fails or is cancelled after resources were created, check whether cleanup ran. Trigger or recommend the janitor workflow or local cleanup only when needed.

## GitHub Actions

Use the GitHub UI when explaining to a human:

1. Open GitHub Actions.
2. Select the `Test` workflow.
3. Click `Run workflow`.
4. Select the branch.
5. Choose `bats_suite`: `vm`, `cloud`, or `all`.
6. Start the run and inspect the resulting jobs.

Use `gh` when operating from the terminal:

```bash
gh workflow run test.yml --ref <branch> -f bats_suite=vm
gh workflow run test.yml --ref <branch> -f bats_suite=cloud
gh workflow run test.yml --ref <branch> -f bats_suite=all
```

Find and monitor runs:

```bash
gh run list --workflow test.yml --branch <branch> --limit 5
gh run watch <run-id>
gh run view <run-id> --log-failed
```

## Local Runs

Use non-interactive commands for agent-driven local execution:

```bash
CI=true ./bats.sh ci vm ubuntu24
CI=true ./bats.sh ci cloud aws
CI=true ./bats.sh ci cloud do
CI=true ./bats.sh ci cloud all
```

Use interactive commands only when the user is driving the terminal:

```bash
./bats.sh run vm
./bats.sh run cloud
./bats.sh cleanup-cloud
./bats.sh cleanup-vm ubuntu24
```

Local requirements:

- VM runs require `bats`, `lima`, and `jq`.
- Cloud runs require `bats`, `jq`, provider credentials/config in `.env`, and any provider CLIs used by cleanup.
- Never print secrets or `.env` contents in the final response.

## Reporting

When a run finishes, report:

- Suite, branch/ref, provider/distro, and whether it ran locally or in GitHub Actions.
- Pass/fail/cancelled status.
- Run URL for GitHub Actions runs.
- Failed job and concise failure excerpt when available.
- Cleanup status for cloud runs.
