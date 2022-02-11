define([
    'jquery',
    'rjsResolver'
], function ($, resolver) {
    'use strict';

    var containerId = '#maincontent';

    return {

        /**
         * Start full page loader action
         */
        startLoader: function () {
            $(containerId).trigger('processStart');
        },

        /**
         * Stop full page loader action
         *
         * @param {Boolean} [forceStop]
         */
        stopLoader: function (forceStop) {
            var $elem = $(containerId),
                stop = $elem.trigger.bind($elem, 'processStop');

            forceStop ? stop() : resolver(stop);
        }
    };
});
