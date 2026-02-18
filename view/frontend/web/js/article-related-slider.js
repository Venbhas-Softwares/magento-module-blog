/**
 * Related products slider - prev/next arrows, smooth slide
 */
define([
    'jquery'
], function ($) {
    'use strict';

    return function (config) {
        var selector = config.selector || '.article-related-products-slider';
        var $block = $(selector);
        if (!$block.length) {
            return;
        }
        var $track = $block.find('.article-related-products-track');
        var $prev = $block.find('.article-related-slider-prev');
        var $next = $block.find('.article-related-slider-next');
        if (!$track.length || !$prev.length || !$next.length) {
            return;
        }
        var itemWidth = 200;
        var gap = 20;
        var step = itemWidth + gap;

        function getMaxScroll() {
            return Math.max(0, $track[0].scrollWidth - $track[0].clientWidth);
        }

        function updateButtons() {
            var maxScroll = getMaxScroll();
            $prev.prop('disabled', $track.scrollLeft() <= 0);
            $next.prop('disabled', maxScroll <= 0 || $track.scrollLeft() >= maxScroll - 1);
        }

        $prev.on('click', function () {
            if ($(this).prop('disabled')) return;
            $track[0].scrollLeft = Math.max(0, $track.scrollLeft() - step);
            updateButtons();
        });

        $next.on('click', function () {
            if ($(this).prop('disabled')) return;
            $track[0].scrollLeft = Math.min(getMaxScroll(), $track.scrollLeft() + step);
            updateButtons();
        });

        $track.on('scroll', updateButtons);
        updateButtons();
    };
});
