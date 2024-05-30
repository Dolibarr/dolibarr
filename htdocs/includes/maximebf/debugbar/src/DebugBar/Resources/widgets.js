if (typeof(PhpDebugBar) == 'undefined') {
    // namespace
    var PhpDebugBar = {};
    PhpDebugBar.$ = jQuery;
}

(function($) {

    /**
     * @namespace
     */
    PhpDebugBar.Widgets = {};

    var csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    /**
     * Replaces spaces with &nbsp; and line breaks with <br>
     *
     * @param {String} text
     * @return {String}
     */
    var htmlize = PhpDebugBar.Widgets.htmlize = function(text) {
        return text.replace(/\n/g, '<br>').replace(/\s/g, "&nbsp;")
    };

    /**
     * Returns a string representation of value, using JSON.stringify
     * if it's an object.
     *
     * @param {Object} value
     * @param {Boolean} prettify Uses htmlize() if true
     * @return {String}
     */
    var renderValue = PhpDebugBar.Widgets.renderValue = function(value, prettify) {
        if (typeof(value) !== 'string') {
            if (prettify) {
                return htmlize(JSON.stringify(value, undefined, 2));
            }
            return JSON.stringify(value);
        }
        return value;
    };

    /**
     * Highlights a block of code
     *
     * @param  {String} code
     * @param  {String} lang
     * @return {String}
     */
    var highlight = PhpDebugBar.Widgets.highlight = function(code, lang) {
        if (typeof(code) === 'string') {
            if (typeof(hljs) === 'undefined') {
                return htmlize(code);
            }
            if (lang) {
                return hljs.highlight(lang, code).value;
            }
            return hljs.highlightAuto(code).value;
        }

        if (typeof(hljs) === 'object') {
            code.each(function(i, e) { hljs.highlightBlock(e); });
        }
        return code;
    };

    /**
     * Creates a <pre> element with a block of code
     *
     * @param  {String} code
     * @param  {String} lang
     * @param  {Number} [firstLineNumber] If provided, shows line numbers beginning with the given value.
     * @param  {Number} [highlightedLine] If provided, the given line number will be highlighted.
     * @return {String}
     */
    var createCodeBlock = PhpDebugBar.Widgets.createCodeBlock = function(code, lang, firstLineNumber, highlightedLine) {
        var pre = $('<pre />').addClass(csscls('code-block'));
        // Add a newline to prevent <code> element from vertically collapsing too far if the last
        // code line was empty: that creates problems with the horizontal scrollbar being
        // incorrectly positioned - most noticeable when line numbers are shown.
        var codeElement = $('<code />').text(code + '\n').appendTo(pre);

        // Add a span with a special class if we are supposed to highlight a line.  highlight.js will
        // still correctly format code even with existing markup in it.
        if ($.isNumeric(highlightedLine)) {
            if ($.isNumeric(firstLineNumber)) {
                highlightedLine = highlightedLine - firstLineNumber + 1;
            }
            codeElement.html(function (index, html) {
                var currentLine = 1;
                return html.replace(/^.*$/gm, function(line) {
                    if (currentLine++ == highlightedLine) {
                        return '<span class="' + csscls('highlighted-line') + '">' + line + '</span>';
                    } else {
                        return line;
                    }
                });
            });
        }

        // Format the code
        if (lang) {
            pre.addClass("language-" + lang);
        }
        highlight(pre);

        // Show line numbers in a list
        if ($.isNumeric(firstLineNumber)) {
            var lineCount = code.split('\n').length;
            var $lineNumbers = $('<ul />').prependTo(pre);
            pre.children().addClass(csscls('numbered-code'));
            for (var i = firstLineNumber; i < firstLineNumber + lineCount; i++) {
                $('<li />').text(i).appendTo($lineNumbers);
            }
        }

        return pre;
    };

    // ------------------------------------------------------------------
    // Generic widgets
    // ------------------------------------------------------------------

    /**
     * Displays array element in a <ul> list
     *
     * Options:
     *  - data
     *  - itemRenderer: a function used to render list items (optional)
     */
    var ListWidget = PhpDebugBar.Widgets.ListWidget = PhpDebugBar.Widget.extend({

        tagName: 'ul',

        className: csscls('list'),

        initialize: function(options) {
            if (!options['itemRenderer']) {
                options['itemRenderer'] = this.itemRenderer;
            }
            this.set(options);
        },

        render: function() {
            this.bindAttr(['itemRenderer', 'data'], function() {
                this.$el.empty();
                if (!this.has('data')) {
                    return;
                }

                var data = this.get('data');
                for (var i = 0; i < data.length; i++) {
                    var li = $('<li />').addClass(csscls('list-item')).appendTo(this.$el);
                    this.get('itemRenderer')(li, data[i]);
                }
            });
        },

        /**
         * Renders the content of a <li> element
         *
         * @param {jQuery} li The <li> element as a jQuery Object
         * @param {Object} value An item from the data array
         */
        itemRenderer: function(li, value) {
            li.html(renderValue(value));
        }

    });

    // ------------------------------------------------------------------

    /**
     * Displays object property/value paris in a <dl> list
     *
     * Options:
     *  - data
     *  - itemRenderer: a function used to render list items (optional)
     */
    var KVListWidget = PhpDebugBar.Widgets.KVListWidget = ListWidget.extend({

        tagName: 'dl',

        className: csscls('kvlist'),

        render: function() {
            this.bindAttr(['itemRenderer', 'data'], function() {
                this.$el.empty();
                if (!this.has('data')) {
                    return;
                }

                var self = this;
                $.each(this.get('data'), function(key, value) {
                    var dt = $('<dt />').addClass(csscls('key')).appendTo(self.$el);
                    var dd = $('<dd />').addClass(csscls('value')).appendTo(self.$el);
                    self.get('itemRenderer')(dt, dd, key, value);
                });
            });
        },

        /**
         * Renders the content of the <dt> and <dd> elements
         *
         * @param {jQuery} dt The <dt> element as a jQuery Object
         * @param {jQuery} dd The <dd> element as a jQuery Object
         * @param {String} key Property name
         * @param {Object} value Property value
         */
        itemRenderer: function(dt, dd, key, value) {
            dt.text(key);
            dd.html(htmlize(value));
        }

    });

    // ------------------------------------------------------------------

    /**
     * An extension of KVListWidget where the data represents a list
     * of variables
     *
     * Options:
     *  - data
     */
    var VariableListWidget = PhpDebugBar.Widgets.VariableListWidget = KVListWidget.extend({

        className: csscls('kvlist varlist'),

        itemRenderer: function(dt, dd, key, value) {
            $('<span />').attr('title', key).text(key).appendTo(dt);

            var v = value;
            if (v && v.length > 100) {
                v = v.substr(0, 100) + "...";
            }
            var prettyVal = null;
            dd.text(v).click(function() {
                if (dd.hasClass(csscls('pretty'))) {
                    dd.text(v).removeClass(csscls('pretty'));
                } else {
                    prettyVal = prettyVal || createCodeBlock(value);
                    dd.addClass(csscls('pretty')).empty().append(prettyVal);
                }
            });
        }

    });

    // ------------------------------------------------------------------

    /**
     * An extension of KVListWidget where the data represents a list
     * of variables whose contents are HTML; this is useful for showing
     * variable output from VarDumper's HtmlDumper.
     *
     * Options:
     *  - data
     */
    var HtmlVariableListWidget = PhpDebugBar.Widgets.HtmlVariableListWidget = KVListWidget.extend({

        className: csscls('kvlist htmlvarlist'),

        itemRenderer: function(dt, dd, key, value) {
            $('<span />').attr('title', key).text(key).appendTo(dt);
            dd.html(value);
        }

    });

    // ------------------------------------------------------------------

    /**
     * Iframe widget
     *
     * Options:
     *  - data
     */
    var IFrameWidget = PhpDebugBar.Widgets.IFrameWidget = PhpDebugBar.Widget.extend({

        tagName: 'iframe',

        className: csscls('iframe'),

        render: function() {
            this.$el.attr({
                seamless: "seamless",
                border: "0",
                width: "100%",
                height: "100%"
            });
            this.bindAttr('data', function(url) { this.$el.attr('src', url); });
        }

    });


    // ------------------------------------------------------------------
    // Collector specific widgets
    // ------------------------------------------------------------------

    /**
     * Widget for the MessagesCollector
     *
     * Uses ListWidget under the hood
     *
     * Options:
     *  - data
     */
    var MessagesWidget = PhpDebugBar.Widgets.MessagesWidget = PhpDebugBar.Widget.extend({

        className: csscls('messages'),

        render: function() {
            var self = this;

            this.$list = new ListWidget({ itemRenderer: function(li, value) {
                if (value.message_html) {
                    var val = $('<span />').addClass(csscls('value')).html(value.message_html).appendTo(li);
                } else {
                    var m = value.message;
                    if (m.length > 100) {
                        m = m.substr(0, 100) + "...";
                    }

                    var val = $('<span />').addClass(csscls('value')).text(m).appendTo(li);
                    if (!value.is_string || value.message.length > 100) {
                        var prettyVal = value.message;
                        if (!value.is_string) {
                            prettyVal = null;
                        }
                        li.css('cursor', 'pointer').click(function () {
                            if (val.hasClass(csscls('pretty'))) {
                                val.text(m).removeClass(csscls('pretty'));
                            } else {
                                prettyVal = prettyVal || createCodeBlock(value.message, 'php');
                                val.addClass(csscls('pretty')).empty().append(prettyVal);
                            }
                        });
                    }
                }

                if (value.collector) {
                    $('<span />').addClass(csscls('collector')).text(value.collector).prependTo(li);
                }
                if (value.label) {
                    val.addClass(csscls(value.label));
                    $('<span />').addClass(csscls('label')).text(value.label).prependTo(li);
                }
            }});

            this.$list.$el.appendTo(this.$el);
            this.$toolbar = $('<div><i class="phpdebugbar-fa phpdebugbar-fa-search"></i></div>').addClass(csscls('toolbar')).appendTo(this.$el);

            $('<input type="text" aria-label="Search" placeholder="Search" />')
                .on('change', function() { self.set('search', this.value); })
                .appendTo(this.$toolbar);

            this.bindAttr('data', function(data) {
                this.set({ exclude: [], search: '' });
                this.$toolbar.find(csscls('.filter')).remove();

                var filters = [], self = this;
                for (var i = 0; i < data.length; i++) {
                    if (!data[i].label || $.inArray(data[i].label, filters) > -1) {
                        continue;
                    }
                    filters.push(data[i].label);
                    $('<a />')
                        .addClass(csscls('filter'))
                        .text(data[i].label)
                        .attr('rel', data[i].label)
                        .on('click', function() { self.onFilterClick(this); })
                        .appendTo(this.$toolbar);
                }
            });

            this.bindAttr(['exclude', 'search'], function() {
                var data = this.get('data'),
                    exclude = this.get('exclude'),
                    search = this.get('search'),
                    caseless = false,
                    fdata = [];

                if (search && search === search.toLowerCase()) {
                    caseless = true;
                }

                for (var i = 0; i < data.length; i++) {
                    var message = caseless ? data[i].message.toLowerCase() : data[i].message;

                    if ((!data[i].label || $.inArray(data[i].label, exclude) === -1) && (!search || message.indexOf(search) > -1)) {
                        fdata.push(data[i]);
                    }
                }

                this.$list.set('data', fdata);
            });
        },

        onFilterClick: function(el) {
            $(el).toggleClass(csscls('excluded'));

            var excludedLabels = [];
            this.$toolbar.find(csscls('.filter') + csscls('.excluded')).each(function() {
                excludedLabels.push(this.rel);
            });

            this.set('exclude', excludedLabels);
        }

    });

    // ------------------------------------------------------------------

    /**
     * Widget for the TimeDataCollector
     *
     * Options:
     *  - data
     */
    var TimelineWidget = PhpDebugBar.Widgets.TimelineWidget = PhpDebugBar.Widget.extend({

        tagName: 'ul',

        className: csscls('timeline'),

        render: function() {
            this.bindAttr('data', function(data) {

                // ported from php DataFormatter
                var formatDuration = function(seconds) {
                    if (seconds < 0.001)
                        return (seconds * 1000000).toFixed() + 'μs';
                    else if (seconds < 1)
                        return (seconds * 1000).toFixed(2) + 'ms';
                    return (seconds).toFixed(2) +  's';
                };

                this.$el.empty();
                if (data.measures) {
                    var aggregate = {};

                    for (var i = 0; i < data.measures.length; i++) {
                        var measure = data.measures[i];

                        if(!aggregate[measure.label])
                            aggregate[measure.label] = { count: 0, duration: 0 };

                        aggregate[measure.label]['count'] += 1;
                        aggregate[measure.label]['duration'] += measure.duration;

                        var m = $('<div />').addClass(csscls('measure')),
                            li = $('<li />'),
                            left = (measure.relative_start * 100 / data.duration).toFixed(2),
                            width = Math.min((measure.duration * 100 / data.duration).toFixed(2), 100 - left);

                        m.append($('<span />').addClass(csscls('value')).css({
                            left: left + "%",
                            width: width + "%"
                        }));
                        m.append($('<span />').addClass(csscls('label')).text(measure.label + " (" + measure.duration_str + ")"));

                        if (measure.collector) {
                            $('<span />').addClass(csscls('collector')).text(measure.collector).appendTo(m);
                        }

                        m.appendTo(li);
                        this.$el.append(li);

                        if (measure.params && !$.isEmptyObject(measure.params)) {
                            var table = $('<table><tr><th colspan="2">Params</th></tr></table>').addClass(csscls('params')).appendTo(li);
                            for (var key in measure.params) {
                                if (typeof measure.params[key] !== 'function') {
                                    table.append('<tr><td class="' + csscls('name') + '">' + key + '</td><td class="' + csscls('value') +
                                    '"><pre><code>' + measure.params[key] + '</code></pre></td></tr>');
                                }
                            }
                            li.css('cursor', 'pointer').click(function() {
                                var table = $(this).find('table');
                                if (table.is(':visible')) {
                                    table.hide();
                                } else {
                                    table.show();
                                }
                            });
                        }
                    }

                    // convert to array and sort by duration
                    aggregate = $.map(aggregate, function(data, label) {
                       return {
                           label: label,
                           data: data
                       }
                    }).sort(function(a, b) {
                        return b.data.duration - a.data.duration
                    });

                    // build table and add
                    var aggregateTable = $('<table style="display: table; border: 0; width: 99%"></table>').addClass(csscls('params'));
                    $.each(aggregate, function(i, aggregate) {
                        width = Math.min((aggregate.data.duration * 100 / data.duration).toFixed(2), 100);

                        aggregateTable.append('<tr><td class="' + csscls('name') + '">' + aggregate.data.count + ' x ' + aggregate.label + ' (' + width + '%)</td><td class="' + csscls('value') + '">' +
                            '<div class="' + csscls('measure') +'">' +
                                '<span class="' + csscls('value') + '" style="width:' + width + '%"></span>' +
                                '<span class="' + csscls('label') + '">' + formatDuration(aggregate.data.duration) + '</span>' +
                            '</div></td></tr>');
                    });

                    this.$el.append('<li/>').find('li:last').append(aggregateTable);
                }
            });
        }

    });

    // ------------------------------------------------------------------

    /**
     * Widget for the displaying exceptions
     *
     * Options:
     *  - data
     */
    var ExceptionsWidget = PhpDebugBar.Widgets.ExceptionsWidget = PhpDebugBar.Widget.extend({

        className: csscls('exceptions'),

        render: function() {
            this.$list = new ListWidget({ itemRenderer: function(li, e) {
                $('<span />').addClass(csscls('message')).text(e.message).appendTo(li);
                if (e.file) {
                    var header = $('<span />').addClass(csscls('filename')).text(e.file + "#" + e.line);
                    if (e.xdebug_link) {
                        if (e.xdebug_link.ajax) {
                            $('<a title="' + e.xdebug_link.url + '"></a>').on('click', function () {
                                $.ajax(e.xdebug_link.url);
                            }).addClass(csscls('editor-link')).appendTo(header);
                        } else {
                            $('<a href="' + e.xdebug_link.url + '"></a>').addClass(csscls('editor-link')).appendTo(header);
                        }
                    }
                    header.appendTo(li);
                }
                if (e.type) {
                    $('<span />').addClass(csscls('type')).text(e.type).appendTo(li);
                }
                if (e.surrounding_lines) {
                    var pre = createCodeBlock(e.surrounding_lines.join(""), 'php').addClass(csscls('file')).appendTo(li);
                    if (!e.stack_trace_html) {
                        // This click event makes the var-dumper hard to use.
                        li.click(function () {
                            if (pre.is(':visible')) {
                                pre.hide();
                            } else {
                                pre.show();
                            }
                        });
                    }
                }
                if (e.stack_trace_html) {
                    var $trace = $('<span />').addClass(csscls('filename')).html(e.stack_trace_html);
                    $trace.appendTo(li);
                } else if (e.stack_trace) {
                    e.stack_trace.split("\n").forEach(function (trace) {
                        var $traceLine = $('<div />');
                        $('<span />').addClass(csscls('filename')).text(trace).appendTo($traceLine);
                        $traceLine.appendTo(li);
                    });
                }
            }});
            this.$list.$el.appendTo(this.$el);

            this.bindAttr('data', function(data) {
                this.$list.set('data', data);
                if (data.length == 1) {
                    this.$list.$el.children().first().find(csscls('.file')).show();
                }
            });

        }

    });


})(PhpDebugBar.$);
