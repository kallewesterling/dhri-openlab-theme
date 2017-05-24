(function($) {

// Parsley validation rules.
window.Parsley.addValidator( 'lowercase', {
	validateString: function( value ) {
		return value === value.toLowerCase();
	},
	messages: {
		en: 'This field supports lowercase letters only.'
	}
} );

window.Parsley.addValidator( 'nospecialchars', {
	validateString: function( value ) {
		return ! value.match( /[^a-zA-Z0-9]/ );
	},
	messages: {
		en: 'This field supports alphanumeric characters only.'
	}
} );

var iffRecursion = false;
window.Parsley.addValidator( 'iff', {
	validateString: function( value, requirement, instance ) {
		var $partner = $( requirement );
		var isValid = $partner.val() == value;

		if ( iffRecursion ) {
			iffRecursion = false;
		} else {
			iffRecursion = true;
			$partner.parsley().validate();
		}

		return isValid;
	}
} );

function checkPasswordStrength( pw, blacklist ) {
	var score = window.wp.passwordStrength.meter( pw, blacklist, '' );

	var message = window.pwsL10n.short;
	switch ( score ) {
		case 2 :
			return window.pwsL10n.bad;

		case 3 :
			return window.pwsL10n.good;

		case 4 :
			return window.pwsL10n.strong;
	}
}

jQuery(document).ready(function() {
	var $signup_form = $( '#signup_form' );

	$signup_form.parsley( {
		errorsWrapper: '<ul class="parsley-errors-list text-danger"></ul>'
	} ).on( 'field:error', function( formInstance ) {
		this.$element.parent( '.form-group' ).addClass( 'has-error' );
	} ).on( 'field:success', function( formInstance ) {
		this.$element.parent( '.form-group' ).removeClass( 'has-error' );
	} );

	var inputBlacklist = [
		'signup_username',
		'field_1',   // Display Name
	];

	$password_strength_notice = $( '#password-strength-notice' );
	$( 'body' ).on( 'keyup', '#signup_password', function( e ) {
		var blacklistValues = [];
		for ( var i = 0; i < inputBlacklist.length; i++ ) {
			var blacklistField = document.getElementById( inputBlacklist[ i ] );
			if ( blacklistField ) {
				if ( 4 <= blacklistField.value.length ) {
					// Exclude short items. See password-strength-meter.js.
					blacklistValues.push( blacklistField.value );
				}
			}
		}

		var score = window.wp.passwordStrength.meter( e.target.value, blacklistValues, '' );

		var message = window.pwsL10n.short;
		switch ( score ) {
			case 2 :
				message = window.pwsL10n.bad;
			break;

			case 3 :
				message = window.pwsL10n.good;
			break;

			case 4 :
				message = window.pwsL10n.strong;
			break;
		}

		$password_strength_notice
			.show()
			.html( message )
			.removeClass( 'strength-0 strength-1 strength-2 strength-3 strength-4' ).
			addClass( 'strength-' + score );
	} );

    $('#signup_email').on('blur', function (e) {
        var email = $(e.target).val().toLowerCase();

		if ( ! email.length ) {
			return;
		}

		// Fade out and show a 'Checking' message.
        var $emaillabel = $('label[for="signup_email"] div');
		$emaillabel.fadeOut(function () {
			$emaillabel.html( OLReg.strings.dashChecking );
			$emaillabel.css('color', '#000');
			$emaillabel.fadeIn();
		});

		$.post(ajaxurl, {
			action: 'openlab_validate_email',
			'email': email
		}, function( response ) {
			if ( response.success ) {
				$emaillabel.fadeOut(function () {
					$emaillabel.html( OLReg.strings.dashOK );
					$emaillabel.css('color', '#000');
					$emaillabel.fadeIn();
				});
			} else {
				$emaillabel.fadeOut(function () {
					$emaillabel.html(response.data.message);
					$emaillabel.css('color', '#f00');
					$emaillabel.fadeIn();
				});
			}
		} );
    });

    $('input#signup_validation_code').live('blur', function () {
        var code = $(this).val();

        var vcodespan = $('label[for="signup_validation_code"] span');

        $(vcodespan).fadeOut(function () {
            $(vcodespan).html('&mdash; Checking...');
            $(vcodespan).css('color', '#000');
            $(vcodespan).fadeIn();
        });

        /* Handle email verification server side because there we have the functions for it */
        $.post(ajaxurl, {
            action: 'cac_ajax_vcode_check',
            'code': code
        },
                function (response) {
                    if ('1' == response) {
                        $(vcodespan).fadeOut(function () {
                            $(vcodespan).html('&mdash; OK!');
                            $(vcodespan).css('color', '#000');
                            $(vcodespan).fadeIn();
                            $('div#submit')
                        });
                    } else {
                        $(vcodespan).fadeOut(function () {
                            $(vcodespan).html('&mdash; Required for non-CUNY addresses');
                            $(vcodespan).css('color', '#f00');
                            $(vcodespan).fadeIn();
                        });
                    }
                });
    });

    var $account_type_field = $( '#account-type' );
    var $account_type_signup_code_field = $( '#account-type-signup-code' );

    // Ensure that the account type field is set properly from the post
	// @todo
    $account_type_field.val(OLReg.post_data.field_7);
    $account_type_field.children('option').each(function () {
        if (OLReg.post_data.field_7 == $(this).val()) {
            $(this).attr('selected', 'selected');
        }
    });

    $account_type_field.on('change', function () {
        load_account_type_fields();
    });
    load_account_type_fields();

    //load register account type
    function load_account_type_fields() {

		var requiresSignupCode = false;
		var selectedAccountType = $account_type_field.find( ':selected' );
		if ( selectedAccountType.length > 0 ) {
			requiresSignupCode = selectedAccountType.data( 'requires-signup-code' ) > 0;
		}

		if ( requiresSignupCode ) {
			$account_type_signup_code_field.show();
		} else {
			$account_type_signup_code_field.hide();
		}

        var selected_account_type = $account_type_field.val();

        if (document.getElementById('signup_submit')) {
            $('#signup_submit').on('click',function(e){

                var thisElem = $(this);

                if(thisElem.hasClass('btn-disabled')){
                    e.preventDefault();
                    var message = 'Please Enter your Email Address To Continue';
                    $('#submitSrMessage').text(message);
                }

            });

            $.ajax(ajaxurl, {
                data: {
                    action: 'openlab_profile_fields',
                    account_type: $account_type_field.val(),
                    post_data: OLReg.post_data
                },
                method: 'POST',
                success: function (response) {
                    $( '#openlab-profile-fields' ).html(response.data);
                    load_error_messages();
                }
            });
        }
    }

    /**
     * Put registration error messages into the template dynamically.
     *
     * See openlab_registration_errors_object().
     */
    function load_error_messages() {
        jQuery.each(OpenLab_Registration_Errors, function (k, v) {
            $('#' + k).before(v);
        });
    }
});

}(jQuery));
