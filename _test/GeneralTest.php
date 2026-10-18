<?php

namespace dokuwiki\plugin\colorplus\test;

use DokuWikiTest;

/**
 * General tests for the colorplus plugin
 *
 * @group plugin_colorplus
 * @group plugins
 */
class GeneralTest extends DokuWikiTest
{
    /**
     * plugin.info.txt has all fields DokuWiki needs
     */
    public function testPluginInfo(): void
    {
        $file = __DIR__ . '/../plugin.info.txt';
        $this->assertFileExists($file);

        $info = confToHash($file);
        foreach (['base', 'author', 'date', 'name', 'desc', 'url'] as $key) {
            $this->assertArrayHasKey($key, $info);
            $this->assertNotEmpty($info[$key]);
        }
        $this->assertEquals('colorplus', $info['base']);
        $this->assertMatchesRegularExpression('/^https?:\/\//', $info['url']);
        $this->assertMatchesRegularExpression('/^\d\d\d\d-\d\d-\d\d$/', $info['date']);
        $this->assertNotFalse(strtotime($info['date']));
    }

    /**
     * Every language has the same strings as English
     */
    public function testLanguageFiles(): void
    {
        $lang = [];
        include __DIR__ . '/../lang/en/lang.php';
        $english = array_keys($lang['js']);
        sort($english);

        foreach (glob(__DIR__ . '/../lang/*/lang.php') as $file) {
            $lang = [];
            include $file;
            $keys = array_keys($lang['js']);
            sort($keys);
            $this->assertEquals($english, $keys, $file);
        }
    }

    /**
     * Every color name used in the toolbar has a translation
     */
    public function testColorNamesAreTranslated(): void
    {
        $lang = [];
        include __DIR__ . '/../lang/en/lang.php';

        $script = file_get_contents(__DIR__ . '/../script.js');
        // ['name', '#rrggbb'] in the basic colors, ['name', [...]] in the palette
        preg_match_all("/\\['([a-z]+)',\\s*(?:'#|\\[)/", $script, $m);
        $this->assertGreaterThan(30, count($m[1]));
        foreach ($m[1] as $name) {
            $this->assertArrayHasKey($name, $lang['js']);
        }
    }
}
