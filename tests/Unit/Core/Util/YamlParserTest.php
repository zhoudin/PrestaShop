<?php
/**
 * 2007-2019 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace LegacyTests\Unit\Core\Util\File;

use LegacyTests\TestCase\UnitTestCase;
use PrestaShop\PrestaShop\Core\Util\File\YamlParser;

class YamlParserTest extends UnitTestCase
{
    /**
     * @param $yamlFiles
     *
     * @return string
     */
    private function clearCacheFile($yamlFiles)
    {
        $yamlParser = new YamlParser($this->getCacheDir(), false);
        $cacheFile = $yamlParser->getCacheFile($yamlFiles);
        @unlink($cacheFile);

        return $cacheFile;
    }

    public function getConfigDir()
    {
        return _PS_ROOT_DIR_ . '/app/config/';
    }

    public function getCacheDir()
    {
        return _PS_ROOT_DIR_ . '/var/cache/test/';
    }

    /**
     * @dataProvider getYamlFilesProvider
     */
    public function testParserNoCache($yamlFiles)
    {
        $cacheFile = $this->clearCacheFile($yamlFiles);
        $yamlParser = new YamlParser($this->getCacheDir(), false);

        // no cache file
        $config = $yamlParser->parse($yamlFiles);
        $this->assertArrayHasKey('parameters', $config);
        $this->assertFileNotExists($cacheFile);
    }

    /**
     * @dataProvider getYamlFilesProvider
     */
    public function testParserCache($yamlFiles)
    {
        $cacheFile = $this->clearCacheFile($yamlFiles);

        // create the cache file
        $yamlParser = new YamlParser($this->getCacheDir(), true);
        $config = $yamlParser->parse($yamlFiles);
        $this->assertArrayHasKey('parameters', $config);
        $this->assertFileExists($cacheFile);
        $cacheTime = filemtime($cacheFile);

        sleep(1);
        // the source file hasn't changed, the cache file should be reused
        $config = $yamlParser->parse($yamlFiles);
        $this->assertArrayHasKey('parameters', $config);
        $this->assertFileExists($cacheFile);
        $this->assertEquals($cacheTime, filemtime($cacheFile));

        // if source yaml change, the cache should be refreshed
        touch($yamlFiles, time() + 1);
        $config = $yamlParser->parse($yamlFiles);
        $this->assertArrayHasKey('parameters', $config);
        $this->assertFileExists($cacheFile);
        $this->assertNotEquals($cacheTime, filemtime($cacheFile));
        $cacheTime = filemtime($cacheFile);
    }

    /**
     * @dataProvider getYamlFilesProvider
     */
    public function testParserCacheRefresh($yamlFiles)
    {
        $cacheFile = $this->clearCacheFile($yamlFiles);

        // create the cache file
        $yamlParser = new YamlParser($this->getCacheDir(), true);
        $config = $yamlParser->parse($yamlFiles);
        $this->assertArrayHasKey('parameters', $config);
        $this->assertFileExists($cacheFile);
        $cacheTime = filemtime($cacheFile);

        // if the forceRefresh flag is used, the cache should be refreshed
        sleep(1);
        $config = $yamlParser->parse($yamlFiles, true);
        $this->assertArrayHasKey('parameters', $config);
        $this->assertFileExists($cacheFile);
        $this->assertNotEquals($cacheTime, filemtime($cacheFile));
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function getYamlFilesProvider()
    {
        return [[$this->getConfigDir() . '/config_test.yml']];
    }
}
