/**
 * Related products slider - shows config.visibleCount at a time; prev/next scroll to see rest.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    var gap = 20;

    return function (config) {
        var selector = config.selector || '.article-related-products-slider';
        var $block = $(selector);
        if (!$block.length) {
            return;
        }
        var $track = $block.find('.article-related-products-track');
        var $prev = $block.find('.article-related-slider-prev');
        var $next = $block.find('.article-related-slider-next');
        var $items = $track.find('.product-item');
        if (!$track.length || !$prev.length || !$next.length) {
            return;
        }
        var visibleCount = Math.max(1, parseInt(config.visibleCount, 10) || 4);

        function setItemWidths() {
            var trackWidth = $track[0].clientWidth;
            if (trackWidth <= 0) return;
            var itemWidth = (trackWidth - (visibleCount - 1) * gap) / visibleCount;
            $items.css({ 'min-width': itemWidth + 'px', 'max-width': itemWidth + 'px', 'flex': '0 0 ' + itemWidth + 'px' });
        }

        var step = function () {
            var trackWidth = $track[0].clientWidth;
            var itemWidth = (trackWidth - (visibleCount - 1) * gap) / visibleCount;
            return itemWidth + gap;
        };

        function getMaxScroll() {
            return Math.max(0, $track[0].scrollWidth - $track[0].clientWidth);
        }

        function updateButtons() {
            var maxScroll = getMaxScroll();
            $prev.prop('disabled', $track.scrollLeft() <= 0);
            $next.prop('disabled', maxScroll <= 0 || $track.scrollLeft() >= maxScroll - 1);
        }

        setItemWidths();

        $prev.on('click', function () {
            if ($(this).prop('disabled')) return;
            $track[0].scrollLeft = Math.max(0, $track.scrollLeft() - step());
            updateButtons();
        });

        $next.on('click', function () {
            if ($(this).prop('disabled')) return;
            $track[0].scrollLeft = Math.min(getMaxScroll(), $track.scrollLeft() + step());
            updateButtons();
        });

        $track.on('scroll', updateButtons);
        $(window).on('resize.articleRelatedSlider', function () {
            setItemWidths();
            updateButtons();
        });
        updateButtons();
    };
});
