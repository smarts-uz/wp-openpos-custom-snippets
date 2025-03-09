jQuery(document).ready(function ($) {
    var $test_connection_button = $('input[name="stripe_tax_for_woocommerce_button_test_connection_live_key"]');
    var $tax_settings_city_input = $('#stripe_tax_for_woocommerce_id_live_mode_city');
    var $tax_settings_country_select = $('#stripe_tax_for_woocommerce_id_live_mode_country');
    var $tax_settings_postal_code_input = $('#stripe_tax_for_woocommerce_id_live_mode_postal_code');
    var $tax_state_row = $('.stripe_tax_for_woocommerce_state_row');
    var $tax_city_row = $('.stripe_tax_for_woocommerce_city_row');
    var $tax_city_label = $('.stripe_tax_for_woocommerce_city_label');
    var $tax_postal_code_row = $('.stripe_tax_for_woocommerce_postal_code_row');
    var $tax_postal_code_label = $('.stripe_tax_for_woocommerce_postal_code_label');

    function handle_tax_settings_country_change() {
        var $this = $(this);
        var country = $this.val();
        var city_label;
        var postal_code_label;

        $tax_state_row.addClass('stripe_tax_for_woocommerce_hidden');
        $('.stripe_tax_for_woocommerce_state_row_' + country).removeClass('stripe_tax_for_woocommerce_hidden');
        $tax_city_row.removeClass('stripe_tax_for_woocommerce_hidden');
        $tax_postal_code_row.removeClass('stripe_tax_for_woocommerce_hidden');

        if (stripe_tax_for_woocommerce.city_is_district_countries.includes(country)) {
            city_label = stripe_tax_for_woocommerce.city_is_district_label;
        } else if (stripe_tax_for_woocommerce.city_is_town_or_city_countries.includes(country)) {
            city_label = stripe_tax_for_woocommerce.city_is_town_or_city_label;
        } else {
            city_label = stripe_tax_for_woocommerce.city_label;
        }
        $tax_city_label.text(city_label);
        $tax_settings_city_input.attr('placeholder', city_label);

        if (stripe_tax_for_woocommerce.postal_code_is_eircode_countries.includes(country)) {
            postal_code_label = stripe_tax_for_woocommerce.postal_code_is_eircode_label;
        } else if (stripe_tax_for_woocommerce.postal_code_is_zip_countries.includes(country)) {
            postal_code_label = stripe_tax_for_woocommerce.postal_code_is_zip_label;
        } else {
            postal_code_label = stripe_tax_for_woocommerce.postal_code_label;
        }
        $tax_postal_code_label.html(postal_code_label);
        $tax_settings_postal_code_input.attr('placeholder', postal_code_label);

        if (stripe_tax_for_woocommerce.postal_code_no_city_countries.includes(country)) {
            $tax_city_row.addClass('stripe_tax_for_woocommerce_hidden');
        }

        if (stripe_tax_for_woocommerce.postal_code_no_postal_code_countries.includes(country)) {
            $tax_postal_code_row.addClass('stripe_tax_for_woocommerce_hidden');
        }
    }

    $tax_settings_country_select.change(handle_tax_settings_country_change);

    var $tax_registrations_country = $('#stripe_tax_for_woocommerce_id_tax_registration_country');
    var $tax_registrations_us_state = $('#stripe_tax_for_woocommerce_id_tax_registration_us_state');
    var $tax_registration_rows = $('.stripe_tax_for_woocommerce_tax_registration_row');

    var $tax_registration_input_type = $('.stripe_tax_for_woocommerce_tax_registration_input_type');
    var $tax_registration_type_standard = $('#stripe_tax_for_woocommerce_id_tax_registration_type_standard');
    var $tax_registration_type_oss_union = $('#stripe_tax_for_woocommerce_id_tax_registration_type_oss_union');
    var $tax_registration_type_oss_non_union = $('#stripe_tax_for_woocommerce_id_tax_registration_type_oss_non_union');
    var $tax_registration_type_ioss = $('#stripe_tax_for_woocommerce_id_tax_registration_type_ioss');
    var $tax_registration_type_bc = $('#stripe_tax_for_woocommerce_id_tax_registration_type_bc');
    var $tax_registration_type_mb = $('#stripe_tax_for_woocommerce_id_tax_registration_type_mb');
    var $tax_registration_type_sk = $('#stripe_tax_for_woocommerce_id_tax_registration_type_sk');
    var $tax_registration_type_qc = $('#stripe_tax_for_woocommerce_id_tax_registration_type_qc');
    var $tax_registration_type_lease_tax = $('#stripe_tax_for_woocommerce_id_tax_registration_type_us_state_lease_tax');
    var $tax_registration_type_chicago = $('#stripe_tax_for_woocommerce_id_tax_registration_type_us_state_chicago');
    var $tax_registration_type_bloomington = $('#stripe_tax_for_woocommerce_id_tax_registration_type_us_state_bloomington');
    var $tax_registration_type_east_dundee = $('#stripe_tax_for_woocommerce_id_tax_registration_type_us_state_east_dundee');
    var $tax_registration_type_schiller_park = $('#stripe_tax_for_woocommerce_id_tax_registration_type_us_state_schiller_park');
    var $tax_registration_type_evanston = $('#stripe_tax_for_woocommerce_id_tax_registration_type_us_state_evanston');
    var $tax_registration_type_communications = $('#stripe_tax_for_woocommerce_id_tax_registration_type_us_state_communications');

    function make_lock($object) {
        $object.attr('disabled', 'disabled');
        $object.prop('checked', true);
    }

    function handle_tax_registration_country_or_us_state_change() {
        var country = $tax_registrations_country.val();
        var country_selected_text = $('#stripe_tax_for_woocommerce_id_tax_registration_country option:selected').text();
        var state = $tax_registrations_us_state.val();
        var state_selected_text = $('#stripe_tax_for_woocommerce_id_tax_registration_us_state option:selected').text();

        $('.tax_registrations_localize_domestic').text(stripe_tax_for_woocommerce.tax_registrations_localize_domestic.replace('%s', country_selected_text));
        $('.tax_registrations_localize_domestic_description').text(stripe_tax_for_woocommerce.tax_registrations_localize_domestic_description.replace('%s', country_selected_text));
        $('.tax_registrations_localize_local_communications').text(stripe_tax_for_woocommerce.tax_registrations_localize_local_communications.replace('%s', state_selected_text));
        $('.tax_registrations_localize_local_communications_description').text(stripe_tax_for_woocommerce.tax_registrations_localize_local_communications_description.replace('%s', state_selected_text));
        $('.tax_registrations_localize_no_sales_description').text(stripe_tax_for_woocommerce.tax_registrations_localize_no_sales_description.replace('%s', state_selected_text));

        $tax_registration_input_type.removeAttr('disabled');
        $tax_registration_input_type.prop('checked', false);

        var locks = stripe_tax_for_woocommerce.tax_registrations_locks;
        if (locks['countries'].includes(country)) {
            make_lock($tax_registration_type_standard);
        }
        if (locks['oss_union']) {
            make_lock($tax_registration_type_oss_union);
        }
        if (locks['oss_non_union']) {
            make_lock($tax_registration_type_oss_non_union);
        }
        if (locks['ioss']) {
            make_lock($tax_registration_type_ioss);
        }
        if (country === 'CA') {
            if (locks['ca_provinces'].includes('BC')) {
                make_lock($tax_registration_type_bc);
            }
            if (locks['ca_provinces'].includes('MB')) {
                make_lock($tax_registration_type_mb);
            }
            if (locks['ca_provinces'].includes('SK')) {
                make_lock($tax_registration_type_sk);
            }
            if (locks['ca_provinces'].includes('QC')) {
                make_lock($tax_registration_type_qc);
            }
        }

        if (country === 'US') {
            if (locks['local_communications'].includes(state)) {
                make_lock($tax_registration_type_communications);
            }
            if (locks['chicago_lease'].includes(state)) {
                make_lock($tax_registration_type_lease_tax);
            }
            if (locks['chicago_amusement'].includes(state)) {
                make_lock($tax_registration_type_chicago);
            }
            if (locks['bloomington'].includes(state)) {
                make_lock($tax_registration_type_bloomington);
            }
            if (locks['east_dundee'].includes(state)) {
                make_lock($tax_registration_type_east_dundee);
            }
            if (locks['evanston'].includes(state)) {
                make_lock($tax_registration_type_evanston);
            }
            if (locks['schiller_park'].includes(state)) {
                make_lock($tax_registration_type_schiller_park);
            }

        }

        $tax_registration_rows.addClass('stripe_tax_for_woocommerce_hidden');
        if (country === 'US') {
            $('.stripe_tax_for_woocommerce_tax_registration_row_us_state').removeClass('stripe_tax_for_woocommerce_hidden');
            if (stripe_tax_for_woocommerce.tax_registrations_lease_and_amusement_tax_use_states.includes(state)) {
                $('.stripe_tax_for_woocommerce_tax_registration_row_lease_and_amusement').removeClass('stripe_tax_for_woocommerce_hidden');
            }
            if (stripe_tax_for_woocommerce.tax_registrations_local_communications_tax_us_states.includes(state)) {
                $('.stripe_tax_for_woocommerce_tax_registration_row_communications').removeClass('stripe_tax_for_woocommerce_hidden');
            }
            if (stripe_tax_for_woocommerce.tax_registrations_no_sales_tax_us_states.includes(state)) {
                $('.stripe_tax_for_woocommerce_tax_registration_row_no_sales_tax_us_states').removeClass('stripe_tax_for_woocommerce_hidden');
            }
        } else if (country === 'CA') {
            $('.stripe_tax_for_woocommerce_tax_registration_row_ca').removeClass('stripe_tax_for_woocommerce_hidden');
        } else if (stripe_tax_for_woocommerce.tax_registrations_eu_countries.includes(country)) {
            $('.stripe_tax_for_woocommerce_tax_registration_row_eu').removeClass('stripe_tax_for_woocommerce_hidden');
        }
    }

    $('#stripe_tax_for_woocommerce_id_tax_registration_country, #stripe_tax_for_woocommerce_id_tax_registration_us_state').change(handle_tax_registration_country_or_us_state_change);


    $(document.body).on('order-totals-recalculate-before', function (e, data) {
        // 'country', 'postal_code', 'city', 'line1', 'line2', 'state',
        data.stripe_tax_for_woocommerce_customer_user = $('#customer_user').val();
        if ( ! data.stripe_tax_for_woocommerce_customer_user ) {
            data.stripe_tax_for_woocommerce_customer_user = 'guest';
        }
        data.stripe_tax_for_woocommerce_customer_details_shipping_address = {
            country: $('#_shipping_country').val(),
            state: $('#_shipping_state').val(),
            city: $('#_shipping_city').val(),
            postal_code: $('#_shipping_postcode').val(),
            line1: $('#_shipping_address_1').val(),
            line2: $('#_shipping_address_2').val(),
        };
        data.stripe_tax_for_woocommerce_customer_details_billing_address = {
            country: $('#_billing_country').val(),
            state: $('#_billing_state').val(),
            city: $('#_billing_city').val(),
            postal_code: $('#_billing_postcode').val(),
            line1: $('#_billing_address_1').val(),
            line2: $('#_billing_address_2').val(),
        };
    });

    $('#stripe_tax_for_woocommerce_button_id_disconnect_from_stripe').click(function (e) {
        var $this = $(this);

        e.stopPropagation();
        e.preventDefault();

        if (!confirm(stripe_tax_for_woocommerce.disconnect_from_stripe_message_confirmation)) {
            return;
        }

        $this.addClass('stripe_tax_for_woocommerce_button_disabled')
        $.post(stripe_tax_for_woocommerce.ajax_url, {
            _nonce: $('input[name="stripe_tax_for_woocommerce_nonce_disconnect_from_stripe"]').val(),
            action: "stripe_tax_for_woocommerce_disconnect_from_stripe"
        }, function (data) {
            if (data.success) {
                add_message('disconnect_from_stripe', data.data.message, 'success', $this);
                location.replace(stripe_tax_for_woocommerce.current_section_url);
            } else {
                add_message('disconnect_from_stripe', data.data.message, 'error', $this);
                $this.removeClass('stripe_tax_for_woocommerce_button_disabled')
            }
        });

    });

    function remove_message(message_id) {
        $('.stripe_tax_for_woocommerce_message_span_id_' + message_id).remove();
        $('#stripe_tax_for_woocommerce_message_id_' + message_id).remove();
    }

    function add_message(message_id, message, type, $after) {
        remove_message(message_id);
        if (!(['info', 'error', 'success', 'warning'].includes(type))) {
            type = 'success';
        }
        var $message = $('<span class="stripe_tax_for_woocommerce_message_span_id_' + message_id + '"> </span><div class="stripe_tax_for_woocommerce_message"><p><strong></strong></p></div>');
        $message
            .eq(1)
            .attr('id', 'stripe_tax_for_woocommerce_message_id_' + message_id)
            .addClass('stripe_tax_for_woocommerce_message_' + type)
            .find('strong')
            .text(message);
        $after.after($message);
    }

    $test_connection_button.click(function (e) {
        var $this = $(this);
        e.preventDefault();
        e.stopPropagation();
        remove_message('test_connection');
        $test_connection_button.attr('disabled', 'disabled');
        var $key_field = $('input[name="stripe_tax_for_woocommerce_live_key"]');
        $.post(stripe_tax_for_woocommerce.ajax_url, {
            _nonce: $('input[name="stripe_tax_for_woocommerce_nonce_test_connection_live_key"]').val(),
            action: "stripe_tax_for_woocommerce_test_connection",
            key: $key_field.val() ? $key_field.val() : '',
            live: '1'
        }, function (data) {
            if (data.success) {
                add_message('test_connection', data.data.message, 'success', $this);
            } else {
                add_message('test_connection', data.data.message, 'error', $this);
            }
        }).always(function () {
            $test_connection_button.removeAttr('disabled');
        });
    });


    var select2_args = $.extend({
        allowClear: false,
    }, []);

    var $tax_code = $('#_stripe_tax_for_woocommerce_tax_code');

    if ($tax_code.length > 0) {
        $($tax_code).selectWoo(select2_args);
    }

    var $button_add_tax_id = $('.stripe_tax_for_woocommerce_button_add_tax_id');

    $button_add_tax_id.click(function (e) {
        e.stopPropagation();
        e.preventDefault();

        var new_tax_id_row = $(stripe_tax_for_woocommerce.tax_ids_html);
        $('.stripe_tax_for_woocommerce_tax_id_item').last().after(new_tax_id_row);
        var closest_container = new_tax_id_row.closest('.stripe_tax_for_woocommerce_tax_ids');
        var counter = parseInt(closest_container.data('tax_ids_count'));
        new_tax_id_row.find('select').attr('name', 'stripe_tax_for_woocommerce_tax_id[' + counter + ']');
        new_tax_id_row.find('input[type="text"]').attr('name', 'stripe_tax_for_woocommerce_tax_id_value[' + counter + ']');
        closest_container.data('tax_ids_count', counter + 1);

        new_tax_id_row.find('select').selectWoo({
            allowClear: false, width: "400px",
        });

        if ($('.stripe_tax_for_woocommerce_tax_id_item').length > 5) {
            $(this).attr('disabled', 'disabled');
        }
    });

    $('.stripe_tax_for_woocommerce_tax_ids').on('click', '.stripe_tax_for_woocommerce_button_remove_tax_id', function (e) {
        $button_add_tax_id.removeAttr('disabled');
        $(this).closest('.stripe_tax_for_woocommerce_tax_id_item').remove();
    });

    $( 'input, textarea, select, checkbox' ).on( 'change input', function (event) {
        $( '.woocommerce-save-button' ).removeAttr( 'disabled' );
    });

	$( '.woocommerce-save-button' ).closest('form').on('submit', function(event) {
		$( '.woocommerce-save-button' ).css('pointer-events', 'none');
	})
});
