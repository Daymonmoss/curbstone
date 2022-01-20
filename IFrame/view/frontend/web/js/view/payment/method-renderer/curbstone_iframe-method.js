define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'mage/url',
        'Magento_Vault/js/view/payment/vault-enabler',
        'Magento_Customer/js/model/customer',
        'ko'
    ],
    function (Component, $, fullScreenLoader, quote, urlBuilder, VaultEnabler, customer, ko) {
        'use strict';
        var listTokens = ko.observableArray([]);
        return Component.extend({
            isCustomerLoggedIn: customer.isLoggedIn,
            defaults: {
                template: 'Curbstone_IFrame/payment/curbstone_iframe',
                useCardOnfileFlag: false,
                tokenValue: '',
                maskCC: '',
                isCardOnfile: false
            },
            initialize: function () {
                this._super();
                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
                window.checkoutConfig.displayBillingOnPaymentMethod = false;
                this.getListTokens();
                return this;
            },
            getListTokens : function(){
                if(!listTokens().length) {
                    listTokens = ko.observableArray(window.checkoutConfig.payment[this.getCode()].listTokens);
                }
                return listTokens;
            },
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            placeOrderToCurbstone: function () {
                var me = this;
                this.selectPaymentMethod();
                if(me.useCardOnfileFlag) {
                    if (typeof me.tokenValue !== "undefined" && me.tokenValue.length > 0) {
                        this.placeOrder();
                    }
                } else {
                    fullScreenLoader.startLoader();
                    me.getiFrame();
                }
            },
            isVaultEnabled: function () {
                return this.isCustomerLoggedIn() && (window.checkoutConfig.payment[this.getCode()].isEnableCardOnFile != '0') && listTokens().length;
            },
            getVaultCode: function () {
                return window.checkoutConfig.payment[this.getCode()].vaultCode;
            },
            getiFrame: function () {
                var me = this;
                var queryString = {};
                queryString.quoteEmail = quote.guestEmail;
                queryString.cardOnFile = me.isCardOnfile;
                var iFrame_src = urlBuilder.build('curbstone_iframe/index/load');
                var iFrame_width = ($(window).width() < 500) ? $(window).width() : 500;
                var iFrame_height = (document.documentElement.clientHeight / 2) - 325;
                var loading = '<div id="curbstone-modal-content" style="width: ' + iFrame_width + 'px;padding: 0;position: fixed;z-index: 9999;margin: 0 auto;left: 0;right: 0;top: 30%;">' +
                    '<div id="curbstone-modal-modal-child" style="position: relative; background-color: #fff;">' +
                    '<div style="width:100%;text-align:right;">' +
                    '<a href="" id="curbstone-close-iFrame">X</a>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                if (document.getElementById('curbstone-view-detail-processing-modal')) {
                    $('#curbstone-view-detail-processing-modal').remove();
                }
                if (!$('.loading-mask')) {
                    $('body').append('<div class="loading-mask"></div>');
                }
                if (!document.getElementById('curbstone-view-detail-processing-modal')) {
                    var overlay = $(document.createElement('div'));
                    $(overlay).attr('id', 'curbstone-view-detail-processing-modal');
                    overlay.append(loading);
                    $('body').append(overlay);
                    $.get({
                        url: iFrame_src,
                        data: queryString,
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
                            el.css("border", "#ccc 1px solid");
                        }
                    });
                    $('#curbstone-close-iFrame').click(function () {
                        $('#curbstone-view-detail-processing-modal').remove();
                        $('.loading-mask').hide();
                        return false;
                    });
                }
            },
            isAvailableTokens: function () {
                console.log(listTokens().length > 0);
                return listTokens().length > 0;
            },
            selectToken: function () {
                var me = this;
                var tokenSelected = $("input[name='payment[method]store-cart']:checked").attr('id');
                if(typeof tokenSelected !== 'undefined') {
                    tokenSelected = tokenSelected.replace('token_', '');
                    me.tokenValue = tokenSelected;
                    me.getMaskedCard(me.tokenValue);
                }
            },
            getIcons: function (type) {
                return window.checkoutConfig.payment.ccform.icons.hasOwnProperty(type) ?
                    window.checkoutConfig.payment.ccform.icons[type]
                    : false;
            },
            useCardOnfile: function () {
                $('.store-card-list').toggle();
                var me = this;
                if($('#curbstone_iframe_card_on_file').prop('checked')) {
                    me.useCardOnfileFlag = true;
                    $('#curbstone_iframe_enable_vault').parent().hide();
                } else {
                    me.useCardOnfileFlag = false;
                    $('#curbstone_iframe_enable_vault').parent().show();
                }
            },
            getData: function () {
                var me = this;
                var data = {
                    method: this.getCode(),
                    additional_data: {
                        tokenValue: me.tokenValue,
                        cardMasked: me.maskCC
                    }
                };
                return data;
            },
            getMaskedCard : function(token){
                var me = this;
                if(listTokens().length) {
                    listTokens.each(function(element){
                        if(element.token == token) {
                            me.maskCC = element.maskCC;
                        }
                    });
                }
            },
            setCardOnfile: function() {
                var me = this;
                if($('#curbstone_iframe_enable_vault').is(':checked')) {
                    me.isCardOnfile = true;
                    $('#curbstone_iframe_card_on_file').parent().hide();
                } else {
                    me.isCardOnfile = false;
                    $('#curbstone_iframe_card_on_file').parent().show();
                }
            },
        });
    }
);
