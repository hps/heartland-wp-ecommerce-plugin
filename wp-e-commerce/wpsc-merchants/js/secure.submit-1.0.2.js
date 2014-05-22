﻿var hps = (function ($) {
    "use strict";

    var Validator, OptionValidator, FieldValidator, HPS;

    Validator = function (fail, message) {
        this.fail = fail;
        this.message = message;
    };

    Validator.prototype.validate = function () {
        if (this.fail) {
            HPS.error(this.message);
        }
    };

    OptionValidator = function (field, options) {
        this.message = field + " is missing";
        this.fail = HPS.empty(options[field]);
    };

    FieldValidator = function (field, type) {
        this.field = field;
        this.type = type;
        this.element = (typeof field === 'object') ? field : $("#" + field);
        this.message = this.element.attr("id") + " is invalid";
        this.fail = HPS.empty(this.element) || !this.element.is(this.type);
    };

    OptionValidator.prototype = new Validator();
    OptionValidator.prototype.constructor = OptionValidator;
    FieldValidator.prototype = new Validator();
    FieldValidator.prototype.constructor = FieldValidator;

    HPS = {

        Tag: "SecureSubmit",

        Urls: {
            CERT: "https://posgateway.cert.secureexchange.net/Hps.Exchange.PosGateway.Hpf.v1/api/token",
            PROD: "https://api.heartlandportico.com/SecureSubmit.v1/api/token"
        },

        getData: function (element) {
            return element.data(HPS.Tag);
        },

        setData: function (element, data) {
            element.data(HPS.Tag, data);
        },

        hasData: function (element) {
            return typeof HPS.getData(element) === 'object';
        },
				
		tokenize: function (options) {			
			var gateway_url, params, env;

            // add additional service parameters
            params = $.param({
                "api_key": options.data.public_key,
                "object": "token",
                "token_type": "supt",
                "_method": "post",
                "card[number]": $.trim(options.data.number),
                "card[cvc]": $.trim(options.data.cvc),
                "card[exp_month]": $.trim(options.data.exp_month),
                "card[exp_year]": $.trim(options.data.exp_year)
            });

            env = options.data.public_key.split("_")[1];

            if (env === "uat") {
                gateway_url = HPS.Urls.UAT;
            } else if (env === "cert") {
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
                        }
                        // handle exception
                        HPS.error(response.error.message);
                    }
					else if(typeof options.success === 'function') {
						options.success(response);
					}
                }
            });

		},

        empty: function (val) {
            return val === undefined || val.length === 0;
        },

        error: function (message) {
            $.error([HPS.Tag, ": ", message].join(""));
        },

        configureElement: function (options) {

            // set plugin data
            HPS.setData($(this), {
                public_key: options.public_key,
                success: options.success,
                error: options.error,
                validators: [
                    new OptionValidator("public_key", options),
                    new FieldValidator("card_number", "input"),
                    new FieldValidator("card_cvc", "input"),
                    new FieldValidator("exp_month", "input"),
                    new FieldValidator("exp_year", "input")
                ]
            });

            // add event handler for form submission
            $(this).submit(function (e) {

                var theForm, data, i;

                // stop form from submitting
                e.preventDefault();

                // remove name attributes from sensitive fields
                $("#card_number").removeAttr("name");
                $("#card_cvc").removeAttr("name");
                $("#exp_month").removeAttr("name");
                $("#exp_year").removeAttr("name");

                theForm = $(this);

                // get data from storage
                data = HPS.getData(theForm);

                // validate data plugin options
                for (i = 0; i < data.validators.length; i += 1) {
                    data.validators[i].validate();
                }

                // validate form - jQuery validate plugin
                if (typeof theForm.validate === 'function') {
                    theForm.validate();
                    // validation failed
                    if (!theForm.valid()) {
                        return;
                    }
                }

				HPS.tokenize({
					data: {
						public_key: data.public_key,
		                number: $.trim($("#card_number").val()),
		                cvc: $.trim($("#card_cvc").val()),
		                exp_month: $.trim($("#exp_month").val()),
		                exp_year: $.trim($("#exp_year").val())
					},
					success: function(response){

		                // create field and append to form
		                $("<input>").attr({
		                    type: "hidden",
		                    id: "token_value",
		                    name: "token_value",
		                    value: response.token_value
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
					error: function(response){
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
            if (!$(this).is("form") || typeof options !== 'object' || HPS.hasData($(this))) {

                return;
            }

            HPS.configureElement.apply(this, [options]);
        });
    };
	
	return HPS;

}(jQuery));
