define([
    'ko',
    'uiComponent',
    'mage/url',
    'mage/storage',
    'mage/translate',
    'Magento_Payment/js/model/credit-card-validation/credit-card-data',
    'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
    'jquery',
    'Magento_Customer/js/model/customer',
    'Curbstone_CardOnFile/js/model/full-screen-loader'
], function (ko, Component, urlBuilder, storage, $t, creditCardData, cardNumberValidator, $, customer, fullScreenLoader) {
    'use strict';
    return Component.extend({
        defaults: {
            template: 'Curbstone_CardOnFile/cc-form',
            creditCardType: '',
            creditCardExpYear: '',
            creditCardExpMonth: '',
            creditCardNumber: '',
            creditCardSsStartMonth: '',
            creditCardSsStartYear: '',
            creditCardSsIssue: '',
            creditCardVerificationNumber: '',
            selectedCardType: null,
            isShow: false //new
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe([
                    'creditCardType',
                    'creditCardExpYear',
                    'creditCardExpMonth',
                    'creditCardNumber',
                    'creditCardVerificationNumber',
                    'creditCardSsStartMonth',
                    'creditCardSsStartYear',
                    'creditCardSsIssue',
                    'selectedCardType'
                ]);
            return this;
        },

        /**
         * Init component
         */
        initialize: function () {
            var self = this;

            this._super();

            //Set credit card number to credit card data object
            this.creditCardNumber.subscribe(function (value) {
                var result;

                self.selectedCardType(null);

                if (value === '' || value === null) {
                    return false;
                }
                result = cardNumberValidator(value);

                if (!result.isPotentiallyValid && !result.isValid) {
                    return false;
                }

                if (result.card !== null) {
                    self.selectedCardType(result.card.type);
                    creditCardData.creditCard = result.card;
                }

                if (result.isValid) {
                    creditCardData.creditCardNumber = value;
                    self.creditCardType(result.card.type);
                }
            });

            //Set expiration year to credit card data object
            this.creditCardExpYear.subscribe(function (value) {
                creditCardData.expirationYear = value;
            });

            //Set expiration month to credit card data object
            this.creditCardExpMonth.subscribe(function (value) {
                creditCardData.expirationMonth = value;
            });

            //Set cvv code to credit card data object
            this.creditCardVerificationNumber.subscribe(function (value) {
                creditCardData.cvvCode = value;
            });
        },

        /**
         * Get code
         * @returns {String}
         */
        getCode: function() {
            return 'cardonfile';
        },
        getCcAvailableTypes: function () {
            return window.checkoutConfig.payment.curbstone_iframe.availableTypes[this.getCode()];
        },

        /**
         * Get payment icons
         * @param {String} type
         * @returns {Boolean}
         */
        getIcons: function (type) {
            return window.checkoutConfig.payment.curbstone_iframe.icons.hasOwnProperty(type) ?
                window.checkoutConfig.payment.curbstone_iframe.icons[type]
                : false;
        },

        /**
         * Get list of months
         * @returns {Object}
         */
        getCcMonths: function () {
            return window.checkoutConfig.payment.curbstone_iframe.months[this.getCode()];
        },

        /**
         * Get list of years
         * @returns {Object}
         */
        getCcYears: function () {
            return window.checkoutConfig.payment.curbstone_iframe.years[this.getCode()];
        },

        /**
         * Check if current payment has verification
         * @returns {Boolean}
         */
        hasVerification: function () {
            return window.checkoutConfig.payment.curbstone_iframe.hasVerification[this.getCode()];
        },

        /**
         * @deprecated
         * @returns {Boolean}
         */
        hasSsCardType: function () {
            return window.checkoutConfig.payment.curbstone_iframe.hasSsCardType[this.getCode()];
        },

        /**
         * Get image url for CVV
         * @returns {String}
         */
        getCvvImageUrl: function () {
            return window.checkoutConfig.payment.curbstone_iframe.cvvImageUrl[this.getCode()];
        },

        /**
         * Get image for CVV
         * @returns {String}
         */
        getCvvImageHtml: function () {
            return '<img src="' + this.getCvvImageUrl() +
                '" alt="' + $t('Card Verification Number Visual Reference') +
                '" title="' + $t('Card Verification Number Visual Reference') +
                '" />';
        },

        /**
         * @deprecated
         * @returns {Object}
         */
        getSsStartYears: function () {
            return window.checkoutConfig.payment.curbstone_iframe.ssStartYears[this.getCode()];
        },

        /**
         * Get list of available credit card types values
         * @returns {Object}
         */
        getCcAvailableTypesValues: function () {
            return _.map(this.getCcAvailableTypes(), function (value, key) {
                return {
                    'value': key,
                    'type': value
                };
            });
        },

        /**
         * Get list of available month values
         * @returns {Object}
         */
        getCcMonthsValues: function () {
            return _.map(this.getCcMonths(), function (value, key) {
                return {
                    'value': key,
                    'month': value
                };
            });
        },

        /**
         * Get list of available year values
         * @returns {Object}
         */
        getCcYearsValues: function () {
            return _.map(this.getCcYears(), function (value, key) {
                return {
                    'value': key,
                    'year': value
                };
            });
        },

        /**
         * @deprecated
         * @returns {Object}
         */
        getSsStartYearsValues: function () {
            return _.map(this.getSsStartYears(), function (value, key) {
                return {
                    'value': key,
                    'year': value
                };
            });
        },

        /**
         * Is legend available to display
         * @returns {Boolean}
         */
        isShowLegend: function () {
            return false;
        },

        /**
         * Get available credit card type by code
         * @param {String} code
         * @returns {String}
         */
        getCcTypeTitleByCode: function (code) {
            var title = '',
                keyValue = 'value',
                keyType = 'type';

            _.each(this.getCcAvailableTypesValues(), function (value) {
                if (value[keyValue] === code) {
                    title = value[keyType];
                }
            });

            return title;
        },

        /**
         * Prepare credit card number to output
         * @param {String} number
         * @returns {String}
         */
        formatDisplayCcNumber: function (number) {
            return 'xxxx-' + number.substr(-4);
        },

        /**
         * Get credit card details
         * @returns {Array}
         */
        getInfo: function () {
            return [
                {
                    'name': 'Credit Card Type', value: this.getCcTypeTitleByCode(this.creditCardType())
                },
                {
                    'name': 'Credit Card Number', value: this.formatDisplayCcNumber(this.creditCardNumber())
                }
            ];
        },
        isActive: function () {
            return true;
        },
        saveStoreCard: function () {
            if($('#curbstone-card-on-file')) {
                if($('#curbstone-card-on-file').validation() && $('#curbstone-card-on-file').valid()) {
                    $('#curbstone-card-on-file').attr('action', window.checkoutConfig.payment.curbstone_iframe.saveCardUrl);
                    $('#curbstone-card-on-file').submit();
                }
            }
        },
        isButtonActive: function () {
            return true;
        },
        isVaultEnabled: function () {
            return customer.isLoggedIn && (window.checkoutConfig.payment[this.getIframeCode()].isEnableCardOnFile != '0');
        },
        getIframeCode: function() {
            return 'curbstone_iframe';
        },
        showCardOnfile: function() {
            $("#curbstone-card-on-file-wrapper").toggle();
        },
        showIframe: function() {
            if (this.isShow) {
                fullScreenLoader.startLoader();
                var iFrame_src = urlBuilder.build('curbstone_cardonfile/index/load');
                var iFrame_width = ($(window).width() < 500) ? $(window).width() : 500;
                var iFrame_height = (document.documentElement.clientHeight / 2) - 325;
                var loading = '<div id="curbstone-modal-content" style="width: ' + iFrame_width + 'px;padding: 0;position: fixed;z-index: 9999;margin: 0 auto;left: 0;right: 0;top: 30%;background: #fff;border: 2px solid #0c369c;">' +
                    '<div id="curbstone-modal-modal-child" style="position: relative; background-color: #fff;">' +
                    '<div style="text-align:right; padding:10px 15px;">' +
                    '<a href="" id="curbstone-close-iFrame" style="color:#0c369c;text-decoration:none;font-weight: 900;">X</a>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                if (!document.getElementById('curbstone-view-detail-processing-modal')) {
                    var overlay = $(document.createElement('div'));
                    $(overlay).attr('id', 'curbstone-view-detail-processing-modal');
                    $(overlay).css({
                        'position': "fixed",
                        'left': 0,
                        'top': 0,
                        'width': '100%',
                        'height': '100%',
                        'overflow': 'auto',
                        'background': "rgba(255, 255, 255, 0.75)",
                        'z-index': 200
                    });
                    overlay.append(loading);
                    $('body').append(overlay);
                    $.get({
                        url: iFrame_src,
                        data: {},
                        success: function (html) {
                            var iFrame = document.createElement('iFrame');
                            iFrame.src = html.url;
                            var modal = document.getElementById("curbstone-modal-modal-child");
                            modal.appendChild(iFrame);
                            var el = $(iFrame);
                            el.attr('id', "plp-iFrame");
                            el.attr('frameborder', 0);
                            el.attr('allowfullscreen', '');
                            el.attr('sandbox', "allow-top-navigation allow-scripts allow-forms");
                            el.css("width", "100%");
                            el.css("height", "320px");
                            fullScreenLoader.stopLoader();
                        }
                    });
                    $('#curbstone-close-iFrame').click(function () {
                        $('#curbstone-view-detail-processing-modal').remove();
                        return false;
                    });
                }
            }
            this.isShow = true;
        }
    });
});
