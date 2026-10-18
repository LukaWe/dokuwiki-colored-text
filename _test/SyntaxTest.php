<?php

namespace dokuwiki\plugin\colorplus\test;

use DokuWikiTest;

/**
 * Rendering tests for <color> and the table color tags
 *
 * @group plugin_colorplus
 * @group plugins
 */
class SyntaxTest extends DokuWikiTest
{
    protected $pluginsEnabled = ['colorplus'];

    /**
     * Render wiki text to XHTML
     *
     * @param string $text
     * @return string
     */
    protected function render($text)
    {
        $info = [];
        return p_render('xhtml', p_get_instructions($text), $info);
    }

    public function testTextColors(): void
    {
        $html = $this->render('<color red/yellow>a</color> <color /#fff200>b</color> <color :lime>c</color>');
        $this->assertStringContainsString('<span style="color:red;background-color:yellow;">a</span>', $html);
        $this->assertStringContainsString('<span style="background-color:#fff200;">b</span>', $html);
        $this->assertStringContainsString('<span style="background-color:lime;">c</span>', $html);
    }

    public function testInvalidColorIsIgnored(): void
    {
        $html = $this->render('<color red;"x>a</color>');
        $this->assertStringContainsString('<span>a</span>', $html);
        $this->assertStringNotContainsString('red', $html);
    }

    public function testCellColor(): void
    {
        $html = $this->render("| <cellcolor #c62828/#ffffff> a | b |\n");
        $this->assertStringContainsString('<td class="col0" style="background-color:#c62828;color:#ffffff;">', $html);
        $this->assertStringContainsString('<td class="col1">', $html);
    }

    public function testRowColumnAndTableColors(): void
    {
        $html = $this->render("^ <rowcolor #cfd8dc> a ^ <colcolor #e3f2fd/navy> b ^\n| <tablecolor #fffde7> c | d |\n");
        $this->assertStringContainsString(
            '<tr class="row0 plugin_colorplus_rowbg" style="--plugin-colorplus-row-bg:#cfd8dc;">',
            $html
        );
        $this->assertStringContainsString(
            '<table class="inline plugin_colorplus_col1bg plugin_colorplus_col1fg plugin_colorplus_tablebg"' .
            ' style="--plugin-colorplus-col1-bg:#e3f2fd;--plugin-colorplus-col1-fg:navy;' .
            '--plugin-colorplus-table-bg:#fffde7;">',
            $html
        );
    }

    public function testLaterTagWins(): void
    {
        $html = $this->render("| <cellcolor red><cellcolor blue> a |\n");
        $this->assertStringContainsString('style="background-color:red;background-color:blue;"', $html);
    }

    public function testTagsOutsideOfTablesAreIgnored(): void
    {
        $html = $this->render("before <cellcolor red> after\n\n| a |\n\n<rowcolor red>\n");
        $this->assertStringContainsString('before  after', $html);
        $this->assertStringNotContainsString('color', $html);
        $this->assertStringNotContainsString('style=', $html);
    }

    public function testTableTagInsideColor(): void
    {
        $html = $this->render("| <color red><cellcolor #ff0>x</color> |\n");
        $this->assertStringContainsString('<td class="col0" style="background-color:#ff0;">', $html);
        $this->assertStringContainsString('<span style="color:red;">x</span>', $html);
    }

    public function testCellAlignmentIsKept(): void
    {
        $html = $this->render("|  <cellcolor #ff0> centered  |\n");
        $this->assertStringContainsString('<td class="col0 centeralign" style="background-color:#ff0;">', $html);
    }
}
