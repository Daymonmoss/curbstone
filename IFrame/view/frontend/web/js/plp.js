/** htdocs\plp\scripts\plp.js
 * v1.3
 * Last Modified: 3/23/2016
 * Copr. 2018 Curbstone Corporation MLP V0.92
 */

var PLP = (function () {

    // iFrame is hidden by default. Only users with JavaScript enabled should see it.
    var iFrameContainer = document.getElementById('plp-iFrame');
    if (iFrameContainer) { iFrameContainer.style.display = 'block'; }

    // Protect against old browsers that don't support console.log
    if (typeof window.console == "undefined" || typeof window.console.log == "undefined") {
        window.console = { log: function() {} };
    };

    var messageHandlers = {};

    /**
     * @param object msg
     */
    var handleMessage = function (msg) {

        if (!msg || !msg.data) {
            window.console.log('PLP message is empty');
            return;
        }

        var data, messageType;

        try {
            data = JSON.parse(msg.data);
        } catch (err) {
            window.console.log('PLP message is not valid JSON');
            return;
        }

        if (data.plp_message && messageHandlers[data.plp_message]) {
            messageType = data.plp_message;
            delete data.plp_message;
            messageHandlers[messageType](data);
        }
    };

    /**
     * By default, PLP will attempt to bust out of the iFrame.
     * This can be disabled by calling disableCompletionHandling().
     */
    messageHandlers.transaction_completed = function (data) {

        if (data.MPTRGT) { // PLP signaled end of transaction

            var form, field, input;

            form = document.createElement('FORM');
            form.method = 'POST';
            form.action = data.MPTRGT;

            for (field in data) {
                input = document.createElement('INPUT');
                input.type = 'hidden';
                input.name = field;
                input.value = data[field].replace(/&#39;/g, "'");
                form.appendChild(input);
            }

            form.style.display = 'none';
            document.body.appendChild(form);
            form.submit();
        }
    };

    /**
     * Attach listeners to receive messages sent from PLP
     */
    if (window.addEventListener) {
        window.addEventListener('message', function (msg) {
            handleMessage(msg);
        });
    }
    else { // IE8 or earlier
        window.attachEvent('onmessage', function (msg) {
            handleMessage(msg);
        });
    }

    /**
     * Client API
     * Only expose certain functionality to client code
     */
    return {

        onFormLoad: function (handler) {
            messageHandlers.payment_form_loaded = handler;
        },

        onFormSubmit: function (handler) {
            messageHandlers.payment_form_submitted = handler;
        },

        onFormValidationError: function (handler) {
            messageHandlers.payment_form_validation_error = handler;
        },

        onTransactionError: function (handler) {
            messageHandlers.payment_form_error = handler;
        },

        onTransactionCompletion: function (handler) {
            messageHandlers.transaction_completed = handler;
        },

        onServiceUnavailable: function (handler) {
            messageHandlers.service_unavailable = handler;
        }
    };

})();