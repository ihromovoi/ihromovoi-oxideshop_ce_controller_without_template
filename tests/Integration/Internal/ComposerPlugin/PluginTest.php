<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Internal\ComposerPlugin;

use Composer\Composer;
use Composer\Config;
use Composer\IO\NullIO;
use OxidEsales\ComposerPlugin\Plugin;
use OxidEsales\EshopCommunity\Tests\ContainerTrait;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class PluginTest extends IntegrationTestCase
{
    use ContainerTrait;

    public function testShopCacheIsCleared(): void
    {
        $cache = $this->get(TagAwareCacheInterface::class);
        $cache->get('plugin_cache_probe', fn (): string => 'cached');

        $this->getPlugin()->clearShopCache();

        $this->assertSame('recomputed', $cache->get('plugin_cache_probe', fn (): string => 'recomputed'));
    }

    #[DataProvider('packagesEventsDataProvider')]
    public function testShopCacheIsClearedAfterOtherListeners(string $event, string $packagesListener): void
    {
        $listeners = Plugin::getSubscribedEvents()[$event];
        $firstListener = reset($listeners);
        $lastListener = end($listeners);

        $this->assertSame($packagesListener, $firstListener[0]);
        $this->assertSame('clearShopCache', $lastListener[0]);
        $this->assertLessThan(0, $lastListener[1]);
    }

    public static function packagesEventsDataProvider(): array
    {
        return [
            ['post-install-cmd', 'installPackages'],
            ['post-update-cmd', 'updatePackages'],
        ];
    }

    private function getPlugin(): Plugin
    {
        $composer = $this->createStub(Composer::class);
        $composer->method('getConfig')->willReturn(new Config());

        $plugin = new Plugin();
        $plugin->activate($composer, new NullIO());

        return $plugin;
    }
}
