<?php

declare(strict_types=1);

namespace DeployCore\DTOs;

readonly class SiteServerDTO
{
    public function __construct(
        public SiteDTO $site,
        public ServerDTO $server,
    ) {
    }
}
