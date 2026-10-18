<?php

use dokuwiki\Extension\SyntaxPlugin;
use dokuwiki\plugin\colorplus\ColorSpec;

/**
 * colorplus: colored table cells, rows, columns and tables
 *
 * <cellcolor bg/fg>, <rowcolor bg/fg>, <colcolor bg/fg> and <tablecolor bg/fg>
 * placed anywhere inside a table cell.
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  LukaW
 */
class syntax_plugin_colorplus_table extends SyntaxPlugin
{
    /** @inheritdoc */
    public function getType()
    {
        return 'substition';
    }

    /** @inheritdoc */
    public function getSort()
    {
        return 158;
    }

    /** @inheritdoc */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern(
            '<(?:cell|row|col|table)color[ \t]+[^>\n]+>',
            $mode,
            'plugin_colorplus_table'
        );
    }

    /** @inheritdoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        // here the background comes first, the text color is optional
        [$tag, $spec] = preg_split('/[ \t]+/', substr($match, 1, -1), 2);
        [$background, $color] = ColorSpec::split($spec);
        return [substr($tag, 0, -5), $background, $color];
    }

    /** @inheritdoc */
    public function render($format, Doku_Renderer $renderer, $data)
    {
        if ($format !== 'xhtml') return false;

        [$scope, $background, $color] = $data;
        $this->colorTable($renderer, $scope, $background, $color);
        return true;
    }

    /**
     * Color the table cell, row, column or whole table the tag is placed in
     *
     * When the tag is rendered, the opening tags of its cell, row and table
     * have already been written to the document, so the colors are added to
     * them afterwards. A cell gets an inline style. Rows, columns and tables
     * get a CSS class and a CSS variable holding the color; the rules in
     * style.css use them to color all their cells, including those that are
     * rendered later.
     *
     * @param Doku_Renderer $renderer
     * @param string $scope cell, row, col or table
     * @param string|null $background
     * @param string|null $color
     */
    protected function colorTable(Doku_Renderer $renderer, $scope, $background, $color)
    {
        if ($background === null && $color === null) return;

        $cell = $this->openCell($renderer->doc);
        if ($cell === false) return; // not inside a table cell

        if ($scope === 'cell') {
            $styles = [];
            if ($background !== null) $styles['background-color'] = $background;
            if ($color !== null) $styles['color'] = $color;
            $this->addToTag($renderer->doc, $cell, [], $styles);
            return;
        }

        if ($scope === 'row') {
            $pos = $this->lastTag($renderer->doc, 'tr', $cell);
            $name = 'row';
        } else {
            $pos = $this->lastTag($renderer->doc, 'table', $cell);
            $name = 'table';
            if ($scope === 'col') {
                $tag = substr($renderer->doc, $cell, strpos($renderer->doc, '>', $cell) - $cell);
                if (!preg_match('/\sclass=["\']col(\d+)/', $tag, $m)) return;
                $name = 'col' . $m[1];
            }
        }
        if ($pos === false) return;

        $classes = [];
        $styles = [];
        if ($background !== null) {
            $classes[] = 'plugin_colorplus_' . $name . 'bg';
            $styles['--plugin-colorplus-' . $name . '-bg'] = $background;
        }
        if ($color !== null) {
            $classes[] = 'plugin_colorplus_' . $name . 'fg';
            $styles['--plugin-colorplus-' . $name . '-fg'] = $color;
        }
        $this->addToTag($renderer->doc, $pos, $classes, $styles);
    }

    /**
     * Position of the opening tag of the table cell currently being rendered
     *
     * @param string $doc
     * @return int|false false if we are not inside a table cell
     */
    protected function openCell($doc)
    {
        $td = $this->lastTag($doc, 'td');
        $th = $this->lastTag($doc, 'th');
        $pos = ($td === false || ($th !== false && $th > $td)) ? $th : $td;
        if ($pos === false) return false;
        foreach (['</td>', '</th>', '</tr>', '</table>'] as $close) {
            if (strpos($doc, $close, $pos) !== false) return false;
        }
        return $pos;
    }

    /**
     * Position of the last opening tag <$name ...> in $doc before $end
     *
     * @param string $doc
     * @param string $name
     * @param int|null $end
     * @return int|false
     */
    protected function lastTag($doc, $name, $end = null)
    {
        $needle = '<' . $name;
        if ($end === null) $end = strlen($doc);
        while ($end > 0) {
            // the negative offset only finds matches starting before $end
            $pos = strrpos($doc, $needle, $end - strlen($doc) - 1);
            if ($pos === false) return false;
            $next = substr($doc, $pos + strlen($needle), 1);
            if ($next === '>' || ctype_space($next)) return $pos;
            $end = $pos;
        }
        return false;
    }

    /**
     * Add CSS classes and inline styles to the opening tag at $pos in $doc
     *
     * @param string $doc
     * @param int $pos
     * @param string[] $classes
     * @param string[] $styles property => value
     */
    protected function addToTag(&$doc, $pos, $classes, $styles)
    {
        $end = strpos($doc, '>', $pos);
        if ($end === false) return;
        $tag = substr($doc, $pos, $end - $pos);

        if ($classes) {
            if (preg_match('/\sclass=(["\'])(.*?)\1/', $tag, $m, PREG_OFFSET_CAPTURE)) {
                $old = preg_split('/\s+/', $m[2][0], -1, PREG_SPLIT_NO_EMPTY);
                $new = implode(' ', array_unique(array_merge($old, $classes)));
                $tag = substr_replace($tag, $new, $m[2][1], strlen($m[2][0]));
            } else {
                $tag .= ' class="' . implode(' ', $classes) . '"';
            }
        }

        $css = '';
        foreach ($styles as $property => $value) {
            $css .= $property . ':' . $value . ';';
        }
        if ($css !== '') {
            if (preg_match('/\sstyle=(["\'])(.*?)\1/', $tag, $m, PREG_OFFSET_CAPTURE)) {
                // appended declarations win, so a later tag overrides an earlier one
                $old = rtrim(trim($m[2][0]), ';');
                $new = ($old === '' ? '' : $old . ';') . $css;
                $tag = substr_replace($tag, $new, $m[2][1], strlen($m[2][0]));
            } else {
                $tag .= ' style="' . $css . '"';
            }
        }

        $doc = substr_replace($doc, $tag, $pos, $end - $pos);
    }
}
