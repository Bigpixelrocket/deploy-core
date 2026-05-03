<?php

declare(strict_types=1);

use DeployCore\Services\FilesystemService;
use DeployCore\Services\InventoryService;
use Symfony\Component\Filesystem\Filesystem;

it('initializes the default inventory under the .deploy directory', function (): void {
    $filesystem = new FilesystemService(new Filesystem());
    $inventory = new InventoryService($filesystem);
    $originalCwd = getcwd();
    $tmpRoot = sys_get_temp_dir() . '/deploy-core-inventory-service-test-' . bin2hex(random_bytes(8));
    $legacyDirectory = '.deploy' . '-core';

    $filesystem->mkdir($tmpRoot);

    try {
        chdir($tmpRoot);

        $inventory->loadInventoryFile();

        expect($filesystem->exists($tmpRoot . '/.deploy/inventory.yml'))->toBeTrue()
            ->and($filesystem->exists($tmpRoot . '/' . $legacyDirectory . '/inventory.yml'))->toBeFalse()
            ->and($inventory->getInventoryFileStatus())->toContain('.deploy/inventory.yml');
    } finally {
        if (is_string($originalCwd)) {
            chdir($originalCwd);
        }

        $filesystem->remove($tmpRoot);
    }
});
