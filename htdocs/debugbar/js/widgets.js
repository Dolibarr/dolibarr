(function ($) {

    var csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-');

    // Configuration constants for TracingSQLQueriesWidget
    var MAX_BACKTRACE_FRAMES = 64;
    var COOKIE_MAX_AGE_SECONDS = 86400 * 30; // 30 days
    var INTERNAL_DB_CLASSES = ['TraceableDB', 'DoliDB'];
    var INTERNAL_DB_FUNCTIONS = ['query', 'startTracing', 'endTracing'];

    function isFullTracingEnabled() {
        return document.cookie.includes('debugbar_full_tracing=1');
    }

    /**
     * TooltipIndicator
     *
     * A customised indicator class that will provide a better tooltip.
     *
     * Options:
     *  - icon
     *  - title
     *  - tooltip: array('html' => '', 'class' => '')
     *  - data: alias of title
     */
    var TooltipIndicator = PhpDebugBar.DebugBar.TooltipIndicator = PhpDebugBar.DebugBar.Indicator.extend({

        render: function() {
            this.$icon = $('<i />').appendTo(this.$el);
            this.bindAttr('icon', function(icon) {
                if (icon) {
                    this.$icon.attr('class', 'fa fa-' + icon);
                } else {
                    this.$icon.attr('class', '');
                }
            });

            this.bindAttr(['title', 'data'], $('<span />').addClass(csscls('text')).appendTo(this.$el));

            this.$tooltip = $('<span />').addClass(csscls('tooltip disabled')).appendTo(this.$el);
            this.bindAttr('tooltip', function(tooltip) {
                if (tooltip['html']) {
                    tooltipHTML = $('<span />').html(tooltip['html']).addClass(csscls('tooltip-html'));
                    this.$tooltip.html(tooltipHTML).removeClass(csscls('disabled'));
                    if (tooltip['class']) {
                        this.$tooltip.addClass(csscls(tooltip['class']));
                    }
                } else {
                    this.$tooltip.addClass(csscls('disabled'));
                }
            });
        }

    });

    /**
     * LinkIndicator
     *
     * A customised indicator class that will allow "click" behaviour.
     *
     * Options:
     *  - icon
     *  - title
     *  - tooltip
     *  - data: alias of title
     *  - href
     *  - target
     */
    var LinkIndicator = PhpDebugBar.DebugBar.LinkIndicator = PhpDebugBar.DebugBar.Indicator.extend({

        tagName: 'a',

        render: function() {
            LinkIndicator.__super__.render.apply(this);
            this.bindAttr('href', function(href) {
                this.$el.attr('href', href);
            });
            this.bindAttr('target', function(target) {
                this.$el.attr('target', target);
            });
        }

    });

    /**
     * An extension of KVListWidget where the data represents a list
     * of variables
     *
     * Options:
     *  - data
     */
    var HookListWidget = PhpDebugBar.Widgets.HookListWidget = PhpDebugBar.Widgets.KVListWidget.extend({
        className: csscls('widgets-kvlist widgets-hooklist'),

        itemRenderer: function(dt, dd, key, object) {
            $('<span />').attr('title', key).text(key).appendTo(dt);


            dd.html('<span><strong>File: </strong> ' + object.file
                + '</span><span><strong>Line: </strong>' + object.line
				+ '</span><span><strong>Count: </strong>' + object.count
                + '</span><span><strong>Contexts: </strong>' + (object.contexts === null || object.contexts === '' ? 'Not set' : object.contexts)
                + '</span>'
            );
        }
    });


    /**
     * TracingSQLQueriesWidget
     *
     * Extends the vendor SQLQueriesWidget by calling parent render() and then enhancing
     * the rendered DOM with Dolibarr-specific features:
     * - Lightweight tracing toggle icon (eye icon in status bar)
     * - Backtrace display with filtering (skip internal DB frames)
     * - Collapsible backtrace button (icon-only, positioned before copy button)
     *
     * This DOM manipulation approach avoids code duplication and inherits all parent functionality.
     */
    var TracingSQLQueriesWidget = PhpDebugBar.Widgets.SQLQueriesWidget.extend({

        render: function() {
            // Wrap bindAttr BEFORE calling parent render.
            // Parent's render() calls bindAttr('data', ...), so we must intercept it first.
            this.wrapDataBinding(function(data) {
                this.addTracingIcon();
                if (data && data.statements) {
                    this.renderBacktraceInQueries(data.statements);
                }
            });

            // Call parent render() to create the base widget structure
            TracingSQLQueriesWidget.__super__.render.apply(this);
        },

        /**
         * Adds the tracing toggle icon to the status bar.
         * Creates an eye icon that toggles between tracing failed queries only
         * and tracing all queries.
         */
        addTracingIcon: function() {
            var self = this;
            var isTracingEnabled = isFullTracingEnabled();

            this.$tracingButton = $('<span />')
                .addClass(csscls('widgets-tracing-icon'))
                .attr('title', isTracingEnabled ? 'Tracing ALL queries (click to trace failed only)' : 'Tracing failed queries only (click to trace all)')
                .css('cursor', 'pointer')
                .toggleClass(csscls('widgets-tracing-enabled'), isTracingEnabled)
                .appendTo(this.$status);

            if (!this.$status.data('tracing-icon-bound')) {
                this.$status.on('click', '.' + csscls('widgets-tracing-icon'), function() {
                    self.toggleTracing();
                });
                this.$status.data('tracing-icon-bound', true);
            }
        },

        /**
         * Wraps the parent's bindAttr method to intercept data binding and
         * extend it after the rendering of the parent.
         */
        wrapDataBinding: function(postCallback) {
            var self = this;
            var parentBindAttr = this.bindAttr;

            this.bindAttr = function(attr, callback) {
                if (attr === 'data') {
                    // Wrap the data callback in a new function to modify
                    // queries after parent renders them
                    var originalCallback = callback;
                    callback = function(data) {
                        // Call parent's data handler first
                        originalCallback.call(this, data);

                        // Call the provided post-callback
                        if (postCallback) {
                            postCallback.call(self, data);
                        }
                    };
                }
                return parentBindAttr.call(this, attr, callback);
            };
        },

        /**
         * Complete rendered query list items with backtrace buttons.
         * For queries that have backtrace data, adds a collapsible backtrace view.
         *
         * @param {Array<Object>} statements - Array of query statement objects from collector
         */
        renderBacktraceInQueries: function(statements) {
            /* The query records have already been rendered by the parent class.
             * Iterate through rendered query list items to add the backtrace panel
             * and the button to display it. */
            this.$list.$el.find('li').each(function(index) {
                var stmt = statements[index];
                if (!stmt || !stmt.backtrace || stmt.backtrace.length === 0) {
                    return;
                }

                var $li = $(this);

                // Skip if backtrace button already exists
                if ($li.find('.' + csscls('widgets-show-backtrace')).length > 0) {
                    return;
                }

                // Insert backtrace button after copy button
                // (both have float:right, so "after" in DOM = "before" visually)
                var $copyBtn = $li.find('.' + csscls('widgets-copy-clipboard'));
                var $showBacktraceBtn = $('<span title="Show backtrace" />')
                    .addClass(csscls('widgets-show-backtrace'))
                    .css('cursor', 'pointer')
                    .insertAfter($copyBtn);

                // Create hidden backtrace container
                var $backtraceContainer = $('<div />')
			.addClass(csscls('widgets-backtrace-container'))
			.hide()
			.appendTo($li);

                $('<div />')
			.addClass(csscls('widgets-backtrace-header'))
			.html('<strong>Backtrace:</strong>')
			.appendTo($backtraceContainer);

                var $backtraceList = $('<div />')
			.addClass(csscls('widgets-backtrace-list'))
			.appendTo($backtraceContainer);

                // Filter out internal database frames
                var filteredFrames = stmt.backtrace.filter(function(frame) {
                    if (frame.class) {
                        for (var i = 0; i < INTERNAL_DB_CLASSES.length; i++) {
                            if (frame.class.indexOf(INTERNAL_DB_CLASSES[i]) >= 0) {
                                return false;
                            }
                        }
                    }
                    if (INTERNAL_DB_FUNCTIONS.indexOf(frame.function) >= 0) {
                        return false;
                    }
                    return true;
                });

                // Now we can render backtrace frames
                filteredFrames.slice(0, MAX_BACKTRACE_FRAMES).forEach(function(frame) {
                    var $frameLine = $('<div />')
			.addClass(csscls('widgets-backtrace-frame'))
			.appendTo($backtraceList);

                    // Format call signature
                    var call = frame.class
                        ? frame.class + (frame.type || '::') + (frame.function || '')
                        : frame.function || '';

                    if (call) {
                        $('<span />')
				.addClass(csscls('widgets-backtrace-function'))
				.text(call + '()')
				.appendTo($frameLine);
                    }

                    if (frame.file) {
                        var fileDisplay = frame.file;
                        $('<span />')
                            .addClass(csscls('widgets-backtrace-filename'))
                            .text(fileDisplay + (frame.line ? ':' + frame.line : ''))
                            .appendTo($frameLine);
                    }
                });

                /* Setup toggle button event handler to show the backtrace */
                $showBacktraceBtn.on('click', function(event) {
                    if ($backtraceContainer.is(':visible')) {
                        $backtraceContainer.hide();
                        $(this).removeClass('active').attr('title', 'Show backtrace');
                    } else {
                        $backtraceContainer.show();
                        $(this).addClass('active').attr('title', 'Hide backtrace');
                    }
                    event.stopPropagation();
                });
            });
        },

        /**
         * Toggles full tracing mode and persists the setting.
         * Prompts user to reload the page to apply changes.
         */
        toggleTracing: function() {
            var newState = !isFullTracingEnabled();
            document.cookie = 'debugbar_full_tracing=' + (newState ? '1' : '0') +
                '; path=/; max-age=' + COOKIE_MAX_AGE_SECONDS + '; SameSite=Lax';

            this.$tracingButton
                .attr('title', newState ? 'Tracing all queries (click to trace failed only)' : 'Tracing failed queries only (click to trace all)')
                .toggleClass(csscls('widgets-tracing-enabled'), newState);
        }
    });

    /* We need to replace the global widget class with our version so as to
     * override the query rendering */
    PhpDebugBar.Widgets.SQLQueriesWidget = TracingSQLQueriesWidget;

})(PhpDebugBar.$);
