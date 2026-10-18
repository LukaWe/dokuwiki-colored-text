<?php

use dokuwiki\Extension\SyntaxPlugin;
use dokuwiki\plugin\colorplus\ColorSpec;

/**
 * colorplus: colored and highlighted text with <color fg/bg>
 *
 * Based on the color plugin by Christopher Smith and Harald Hanche-Olsen
 * (https://www.dokuwiki.org/plugin:color).
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  LukaW
 */
class syntax_plugin_colorplus_text extends SyntaxPlugin
{
    /** @inheritdoc */
    public function getType()
    {
        return 'formatting';
    }

    /** @inheritdoc */
    public function getAllowedTypes()
    {
        return ['formatting', 'substition', 'disabled'];
    }

    /** @inheritdoc */
    public function getSort()
    {
        return 158;
    }

    /** @inheritdoc */
    public function connectTo($mode)
    {
        if ($this->handlesColorTag()) {
            $this->Lexer->addEntryPattern('<color.*?>(?=.*?</color>)', $mode, 'plugin_colorplus_text');
        }
    }

    /** @inheritdoc */
    public function postConnect()
    {
        if ($this->handlesColorTag()) {
            $this->Lexer->addExitPattern('</color>', 'plugin_colorplus_text');
        }
    }

    /** @inheritdoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        switch ($state) {
            case DOKU_LEXER_ENTER:
                [$color, $background] = ColorSpec::split(substr($match, 6, -1));
                $style = '';
                if ($color !== null) $style .= 'color:' . $color . ';';
                if ($background !== null) $style .= 'background-color:' . $background . ';';
                return [$state, $style];

            case DOKU_LEXER_UNMATCHED:
                return [$state, $match];
        }
        return [$state, null];
    }

    /** @inheritdoc */
    public function render($format, Doku_Renderer $renderer, $data)
    {
        [$state, $match] = $data;

        // the colored text itself, for every output format
        if ($state === DOKU_LEXER_UNMATCHED) {
            $renderer->cdata($match);
            return true;
        }

        if ($format === 'xhtml') {
            if ($state === DOKU_LEXER_ENTER) {
                $renderer->doc .= ($match === '') ? '<span>' : '<span style="' . $match . '">';
            } elseif ($state === DOKU_LEXER_EXIT) {
                $renderer->doc .= '</span>';
            }
            return true;
        }

        // export via the ODT plugin
        if ($format === 'odt') {
            if ($state === DOKU_LEXER_ENTER && method_exists($renderer, '_odtSpanOpenUseCSS')) {
                $renderer->_odtSpanOpenUseCSS(null, 'style="' . $match . '"');
            } elseif ($state === DOKU_LEXER_EXIT && method_exists($renderer, '_odtSpanClose')) {
                $renderer->_odtSpanClose();
            }
            return true;
        }

        return false;
    }

    /**
     * Whether this plugin handles <color>
     *
     * The original color plugin renders <color> the same way, so if it is
     * installed, it gets the tag and both plugins can be used side by side.
     *
     * @return bool
     */
    protected function handlesColorTag()
    {
        return plugin_isdisabled('color');
    }
}
