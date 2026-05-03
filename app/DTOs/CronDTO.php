<?php

declare(strict_types=1);

namespace DeployCore\DTOs;

readonly class CronDTO
{
    /**
     * Create a CronDTO containing the cron's script path and schedule.
     *
     * @param string $script   Script path relative to project directory (e.g., .deploy/scripts/cron.sh).
     * @param string $schedule Cron schedule expression (e.g., "* * * * *").
     */
    public function __construct(
        public string $script,
        public string $schedule,
    ) {
    }
}
