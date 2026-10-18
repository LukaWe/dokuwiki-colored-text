/* colorplus: color pickers for the DokuWiki editor toolbar */
/* plain ES5 without jQuery, see https://www.dokuwiki.org/devel:toolbar */

var plugin_colorplus = {
    icobase: '../../plugins/colorplus/images/',

    // first row: black, white and the colors of the original color plugin
    basic: [
        ['black', '#000000'], ['white', '#ffffff'],
        ['lightgray', '#c3c3c3'], ['rose', '#ffaec9'], ['red', '#ed1c24'],
        ['orange', '#ff7f27'], ['gold', '#ffc90e'], ['yellow', '#fff200'],
        ['lightyellow', '#efe4b0'], ['green', '#22b14c'], ['lime', '#b5e61d'],
        ['turquoise', '#00a2e8'], ['lightturquoise', '#99d9ea'],
        ['bluegray', '#7092be'], ['lavender', '#c8bfe7']
    ],

    // Material Design palette: one column per hue, one row per shade (light to dark)
    shades: [50, 100, 200, 300, 400, 500, 600, 700, 800, 900],
    palette: [
        ['red',        ['#ffebee', '#ffcdd2', '#ef9a9a', '#e57373', '#ef5350', '#f44336', '#e53935', '#d32f2f', '#c62828', '#b71c1c']],
        ['pink',       ['#fce4ec', '#f8bbd0', '#f48fb1', '#f06292', '#ec407a', '#e91e63', '#d81b60', '#c2185b', '#ad1457', '#880e4f']],
        ['purple',     ['#f3e5f5', '#e1bee7', '#ce93d8', '#ba68c8', '#ab47bc', '#9c27b0', '#8e24aa', '#7b1fa2', '#6a1b9a', '#4a148c']],
        ['deeppurple', ['#ede7f6', '#d1c4e9', '#b39ddb', '#9575cd', '#7e57c2', '#673ab7', '#5e35b1', '#512da8', '#4527a0', '#311b92']],
        ['indigo',     ['#e8eaf6', '#c5cae9', '#9fa8da', '#7986cb', '#5c6bc0', '#3f51b5', '#3949ab', '#303f9f', '#283593', '#1a237e']],
        ['blue',       ['#e3f2fd', '#bbdefb', '#90caf9', '#64b5f6', '#42a5f5', '#2196f3', '#1e88e5', '#1976d2', '#1565c0', '#0d47a1']],
        ['lightblue',  ['#e1f5fe', '#b3e5fc', '#81d4fa', '#4fc3f7', '#29b6f6', '#03a9f4', '#039be5', '#0288d1', '#0277bd', '#01579b']],
        ['cyan',       ['#e0f7fa', '#b2ebf2', '#80deea', '#4dd0e1', '#26c6da', '#00bcd4', '#00acc1', '#0097a7', '#00838f', '#006064']],
        ['teal',       ['#e0f2f1', '#b2dfdb', '#80cbc4', '#4db6ac', '#26a69a', '#009688', '#00897b', '#00796b', '#00695c', '#004d40']],
        ['green',      ['#e8f5e9', '#c8e6c9', '#a5d6a7', '#81c784', '#66bb6a', '#4caf50', '#43a047', '#388e3c', '#2e7d32', '#1b5e20']],
        ['lightgreen', ['#f1f8e9', '#dcedc8', '#c5e1a5', '#aed581', '#9ccc65', '#8bc34a', '#7cb342', '#689f38', '#558b2f', '#33691e']],
        ['lime',       ['#f9fbe7', '#f0f4c3', '#e6ee9c', '#dce775', '#d4e157', '#cddc39', '#c0ca33', '#afb42b', '#9e9d24', '#827717']],
        ['yellow',     ['#fffde7', '#fff9c4', '#fff59d', '#fff176', '#ffee58', '#ffeb3b', '#fdd835', '#fbc02d', '#f9a825', '#f57f17']],
        ['amber',      ['#fff8e1', '#ffecb3', '#ffe082', '#ffd54f', '#ffca28', '#ffc107', '#ffb300', '#ffa000', '#ff8f00', '#ff6f00']],
        ['orange',     ['#fff3e0', '#ffe0b2', '#ffcc80', '#ffb74d', '#ffa726', '#ff9800', '#fb8c00', '#f57c00', '#ef6c00', '#e65100']],
        ['deeporange', ['#fbe9e7', '#ffccbc', '#ffab91', '#ff8a65', '#ff7043', '#ff5722', '#f4511e', '#e64a19', '#d84315', '#bf360c']],
        ['brown',      ['#efebe9', '#d7ccc8', '#bcaaa4', '#a1887f', '#8d6e63', '#795548', '#6d4c41', '#5d4037', '#4e342e', '#3e2723']],
        ['grey',       ['#fafafa', '#f5f5f5', '#eeeeee', '#e0e0e0', '#bdbdbd', '#9e9e9e', '#757575', '#616161', '#424242', '#212121']],
        ['bluegrey',   ['#eceff1', '#cfd8dc', '#b0bec5', '#90a4ae', '#78909c', '#607d8b', '#546e7a', '#455a64', '#37474f', '#263238']]
    ],

    // what the table picker colors: <cellcolor>, <rowcolor>, <colcolor>, <tablecolor>
    scopes: ['cell', 'row', 'col', 'table'],

    // hidden <input type="color"> used for custom colors
    input: null,
    onpick: null
};

plugin_colorplus.lang = function (key) {
    var strings = (window.LANG && LANG.plugins && LANG.plugins.colorplus) || {};
    return strings[key] || key;
};

// true if white text is easier to read than black text on the given #rrggbb background
plugin_colorplus.isDark = function (color) {
    var m = /^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec(color),
        weights = [0.2126, 0.7152, 0.0722],
        luminance = 0, c, i;
    if (!m) return false;
    for (i = 0; i < 3; i++) {
        c = parseInt(m[i + 1], 16) / 255;
        c = (c <= 0.03928) ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
        luminance += weights[i] * c;
    }
    // above this the contrast ratio to black is higher than to white
    return luminance < 0.179;
};

// insert the markup for color at the cursor or around the selection
plugin_colorplus.insert = function (mode, color, edid, picker) {
    var light = plugin_colorplus.isDark(color) ? '#ffffff' : '',
        scope;
    if (mode === 'text') {
        insertTags(edid, '<color ' + color + '>', '</color>', plugin_colorplus.lang('sample_text'));
    } else if (mode === 'bg') {
        insertTags(edid, '<color ' + light + '/' + color + '>', '</color>', plugin_colorplus.lang('sample_bg'));
    } else {
        scope = (picker && picker.getAttribute('data-colorplus-scope')) || 'cell';
        insertTags(edid, '<' + scope + 'color ' + color + (light ? '/' + light : '') + '>', '', '');
    }
    pickerClose();
};

// mark btn as the selected scope of the table picker
plugin_colorplus.setScope = function (picker, btn, scope) {
    var buttons = picker.querySelectorAll('.plugin_colorplus_scope'),
        i;
    for (i = 0; i < buttons.length; i++) {
        buttons[i].classList.remove('plugin_colorplus_active');
        buttons[i].setAttribute('aria-pressed', 'false');
    }
    btn.classList.add('plugin_colorplus_active');
    btn.setAttribute('aria-pressed', 'true');
    picker.setAttribute('data-colorplus-scope', scope);
};

// ask for a custom color with the browser's color dialog and pass it to callback
plugin_colorplus.pick = function (btn, callback) {
    var self = plugin_colorplus,
        input = self.input,
        rect, color;
    if (!input) {
        input = document.createElement('input');
        input.setAttribute('type', 'color');
        input.setAttribute('tabindex', '-1');
        input.setAttribute('aria-hidden', 'true');
        input.className = 'plugin_colorplus_input';
        document.body.appendChild(input);
        input.addEventListener('change', function () {
            if (self.onpick) self.onpick(input.value);
            self.onpick = null;
        });
        self.input = input;
    }

    if (input.type !== 'color') {
        // no color dialog in this browser, ask for any CSS color instead
        color = window.prompt(self.lang('custom_prompt'), '#');
        if (color && color.trim() !== '#') callback(color.trim());
        return;
    }

    // open the dialog next to the button
    rect = btn.getBoundingClientRect();
    input.style.left = (rect.left + window.pageXOffset) + 'px';
    input.style.top = (rect.bottom + window.pageYOffset) + 'px';
    // "change" only fires for a new value, so start from a known one
    input.value = '#000000';
    self.onpick = callback;
    try {
        input.showPicker();
    } catch (e) {
        input.click(); // browsers without showPicker()
    }
};

// build the toolbar picker for mode "text", "bg" or "table"
plugin_colorplus.picker = function (mode, icon) {
    var self = plugin_colorplus,
        list = [],
        i, j;

    function button(props, classes) {
        props.type = 'plugin_colorplus';
        props.mode = mode;
        props['class'] = 'plugin_colorplus_btn ' + classes;
        list.push(props);
    }

    function swatch(color, name, newrow) {
        button({
            color: color,
            title: name + ' (' + color + ')',
            icon: self.icobase + 'swatch.png'
        }, 'plugin_colorplus_swatch' + (newrow ? ' plugin_colorplus_newrow' : ''));
    }

    if (mode === 'table') {
        for (i = 0; i < self.scopes.length; i++) {
            button({
                scope: self.scopes[i],
                title: self.lang('scope') + ': ' + self.lang('scope_' + self.scopes[i]),
                label: self.lang('scope_' + self.scopes[i]),
                icon: self.icobase + 'scope_' + self.scopes[i] + '.png'
            }, 'plugin_colorplus_wide plugin_colorplus_scope' +
                (i ? '' : ' plugin_colorplus_newrow plugin_colorplus_active'));
        }
    }

    for (i = 0; i < self.basic.length; i++) {
        swatch(self.basic[i][1], self.lang(self.basic[i][0]), i === 0);
    }
    button({
        custom: true,
        title: self.lang('custom_title'),
        label: self.lang('custom'),
        icon: self.icobase + 'custom.png'
    }, 'plugin_colorplus_wide');

    for (j = 0; j < self.shades.length; j++) {
        for (i = 0; i < self.palette.length; i++) {
            swatch(self.palette[i][1][j], self.lang(self.palette[i][0]) + ' ' + self.shades[j], i === 0);
        }
    }

    return {
        type: 'picker',
        title: self.lang('picker_' + mode),
        icon: self.icobase + icon,
        'class': 'pk_plugin_colorplus',
        list: list
    };
};

// called by initToolbar() for every button of type "plugin_colorplus" in the pickers
window.addBtnActionPlugin_colorplus = function ($btn, props, edid) {
    var self = plugin_colorplus,
        btn = ($btn && $btn.jquery) ? $btn[0] : $btn,
        label;

    if (props.label) {
        label = document.createElement('span');
        label.textContent = props.label;
        btn.appendChild(label);
    }
    if (props.scope) {
        btn.setAttribute('aria-pressed', btn.classList.contains('plugin_colorplus_active') ? 'true' : 'false');
    } else if (!props.custom) {
        btn.querySelector('img').style.backgroundColor = props.color;
    }

    btn.addEventListener('click', function (e) {
        var picker = btn.closest('.picker');
        e.preventDefault();
        if (props.scope) {
            self.setScope(picker, btn, props.scope);
        } else if (props.custom) {
            self.pick(btn, function (color) {
                self.insert(props.mode, color, edid, picker);
            });
        } else {
            self.insert(props.mode, props.color, edid, picker);
        }
    });
    return edid;
};

if (typeof window.toolbar !== 'undefined') {
    window.toolbar.push(plugin_colorplus.picker('text', 'toolbar_textcolor.png'));
    window.toolbar.push(plugin_colorplus.picker('bg', 'toolbar_bgcolor.png'));
    window.toolbar.push(plugin_colorplus.picker('table', 'toolbar_tablecolor.png'));
}
