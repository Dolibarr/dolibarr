(function ($) {

    var csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-');

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

})(PhpDebugBar.$);