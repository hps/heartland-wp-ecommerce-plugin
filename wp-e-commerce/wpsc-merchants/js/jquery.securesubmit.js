/*global $, jQuery*/
var hps = (function ($) {
    "use strict";

    var HPS;

    HPS = {

        Tag: "SecureSubmit",

        Urls: {
            CERT: "https://cert.api2.heartlandportico.com/Hps.Exchange.PosGateway.Hpf.v1/api/token",
            PROD: "https://api2.heartlandportico.com/SecureSubmit.v1/api/token"
        },

        tokenize: function (options) {
            var gateway_url, params, env;
            var number = $.trim(options.data.number);
            var exp_month = $.trim(options.data.exp_month);
            var exp_year = $.trim(options.data.exp_year);

            // add additional service parameters
            params = $.param({
                "api_key": options.data.public_key,
                "object": "token",
                "token_type": "supt",
                "_method": "post",
                "card[number]": number,
                "card[cvc]": $.trim(options.data.cvc),
                "card[exp_month]": exp_month,
                "card[exp_year]": exp_year
            });

            env = options.data.public_key.split("_")[1];

            if (env === "cert") {
                gateway_url = HPS.Urls.CERT;
            } else {
                gateway_url = HPS.Urls.PROD;
            }


            var d = new Date();
            if (parseInt(exp_year) < d.getFullYear()) {
                options.error("The expiration year is in the past.");
                return;
            }

            var cardType = '';

            var re = {
                visa: /^4[0-9]{12}(?:[0-9]{3})?$/,
                mastercard: /^5[1-5][0-9]{14}$/,
                amex: /^3[47][0-9]{13}$/,
                diners: /^3(?:0[0-5]|[68][0-9])[0-9]{11}$/,
                discover: /^6(?:011|5[0-9]{2})[0-9]{12}$/,
                jcb: /^(?:2131|1800|35\d{3})\d{11}$/
            };

            if (re.visa.test(number)) {
                cardType = 'visa';
            } else if (re.mastercard.test(number)) {
                cardType = 'mastercard';
            } else if (re.amex.test(number)) {
                cardType = 'amex';
            } else if (re.diners.test(number)) {
                cardType = 'diners';
            } else if (re.discover.test(number)) {
                cardType = 'discover';
            } else if (re.jcb.test(number)) {
                cardType = 'jcb';
            }

            // request token
            $.ajax({
                cache: false,
                url: gateway_url,
                data: params,
                dataType: "jsonp",
                success: function (response) {

                    // Request failed, handle error
                    if (typeof response.error === 'object') {
                        // call error handler if provided and valid
                        if (typeof options.error === 'function') {
                            options.error(response.error);
                        }
                        else {
                            // handle exception
                            HPS.error(response.error.message);
                        }
                    } else if (typeof options.success === 'function') {
                        response.card_type = cardType;
                        response.exp_month = exp_month;
                        response.exp_year = exp_year;

                        options.success(response);
                    }
                }
            });
        },

        tokenize_swipe: function (options) {
            var gateway_url, params, env;

            params = $.param({
                "api_key": options.data.public_key,
                "object": "token",
                "token_type": "supt",
                "_method": "post",
                "card[track_method]": "swipe",
                "card[track]": $.trim(options.data.track)
            });

            env = options.data.public_key.split("_")[1];

            if (env === "cert") {
                gateway_url = HPS.Urls.CERT;
            } else {
                gateway_url = HPS.Urls.PROD;
            }

            // request token
            $.ajax({
                cache: false,
                url: gateway_url,
                data: params,
                dataType: "jsonp",
                success: function (response) {

                    // Request failed, handle error
                    if (typeof response.error === 'object') {
                        // call error handler if provided and valid
                        if (typeof options.error === 'function') {
                            options.error(response.error);
                        } else {
                            // handle exception
                            HPS.error(response.error.message);
                        }
                    } else if (typeof options.success === 'function') {
                        options.success(response);
                    }
                }
            });
        },

        trim: function (string) {

            if (string !== undefined && typeof string === "string" ) {

                string = string.toString().replace(/^\s\s*/, '').replace(/\s\s*$/, '');
            }

            return string;
        },

        empty: function (val) {
            return val === undefined || val.length === 0;
        },

        error: function (message) {
            $.error([HPS.Tag, ": ", message].join(""));
        },

        configureElement: function (options) {

            // set plugin data
            $(this).data(HPS.Tag, {
                public_key: options.public_key,
                success: options.success,
                error: options.error
            });

            // add event handler for form submission
            $(this).submit(function (e) {

                var theForm, data, i, cardType;

                // stop form from submitting
                e.preventDefault();

                // remove name attributes from sensitive fields
                $("#card_number").removeAttr("name");
                $("#card_cvc").removeAttr("name");
                $("#exp_month").removeAttr("name");
                $("#exp_year").removeAttr("name");

                theForm = $(this);

                // get data from storage
                data = theForm.data(HPS.Tag);

                // validate form - jQuery validate plugin
                if (typeof theForm.validate === 'function') {
                    theForm.validate();
                    // validation failed
                    if (!theForm.valid()) {
                        return;
                    }
                }

                var number = $.trim($("#card_number").val());
                var exp_month = $.trim($("#exp_month").val());
                var exp_year = $.trim($("#exp_year").val());

                var d = new Date();
                if (parseInt(exp_year) < d.getFullYear()) {
                    HPS.error("The expiration year is in the past.");
                    return;
                }

                HPS.tokenize({
                    data: {
                        public_key: data.public_key,
                        number: number,
                        cvc: $.trim($("#card_cvc").val()),
                        exp_month: exp_month,
                        exp_year: exp_year
                    },
                    success: function (response) {
                        // create field and append to form
                        $("<input>").attr({
                            type: "hidden",
                            id: "token_value",
                            name: "token_value",
                            value:  response.token_value
                        }).appendTo(theForm);

                        var re = {
                            visa: /^4[0-9]{12}(?:[0-9]{3})?$/,
                            mastercard: /^5[1-5][0-9]{14}$/,
                            amex: /^3[47][0-9]{13}$/,
                            diners: /^3(?:0[0-5]|[68][0-9])[0-9]{11}$/,
                            discover: /^6(?:011|5[0-9]{2})[0-9]{12}$/,
                            jcb: /^(?:2131|1800|35\d{3})\d{11}$/
                        };

                        if (re.visa.test(number)) {
                            cardType = 'visa';
                        } else if (re.mastercard.test(number)) {
                            cardType = 'mastercard';
                        } else if (re.amex.test(number)) {
                            cardType = 'amex';
                        } else if (re.diners.test(number)) {
                            cardType = 'diners';
                        } else if (re.discover.test(number)) {
                            cardType = 'discover';
                        } else if (re.jcb.test(number)) {
                            cardType = 'jcb';
                        }

                        $("<input>").attr({
                            type: "hidden",
                            id: "card_type",
                            name: "card_type",
                            value: cardType
                        }).appendTo(theForm);

                        $("<input>").attr({
                            type: "hidden",
                            id: "exp_month",
                            name: "exp_month",
                            value: exp_month
                        }).appendTo(theForm);

                        $("<input>").attr({
                            type: "hidden",
                            id: "exp_year",
                            name: "exp_year",
                            value: exp_year
                        }).appendTo(theForm);

                        $("<input>").attr({
                            type: "hidden",
                            id: "last_four",
                            name: "last_four",
                            value: number.slice(-4)
                        }).appendTo(theForm);

                        // success handler provided
                        if (typeof data.success === 'function') {
                            // call the handler with payload
                            if (data.success(response) === false) {
                                return; // stop processing
                            }
                        }

                        theForm.unbind('submit'); // unbind event handler
                        theForm.submit(); // submit the form
                    },
                    error: function (response) {
                        if (typeof data.error === 'function') {
                            data.error(response);
                        }
                    }
                });

            });
        }
    };

    $.fn.SecureSubmit = function (options) {

        return this.each(function () {
            if (!$(this).is("form") || typeof options !== 'object' || $.hasData($(this))) {

                return;
            }

            HPS.configureElement.apply(this, [options]);
        });
    };

    return HPS;
}(jQuery));
