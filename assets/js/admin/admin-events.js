(function ($) {
	"use strict";
	var TP_Event_Admin = {};

	TP_Event_Admin.init = function () {

		// widgets
		var forms = $('#widgets-right .widget-content');
		for (var i = 0; i <= forms.length; i++) {
			var form = $(forms[i]);

			form.find('.tp_event_admin_widget:first').addClass('active');

			form.find('.tp_event_widget_tab li a:first').addClass('button-primary');
			$(document).on('click', '.tp_event_widget_tab li a', function (e) {
				e.preventDefault();
				var tab_content = $(this).attr('data-tab'),
					widget_content = $(this).parents('.widget-content'),
					parent = $(this).parents('.tp_event_widget_tab');
				parent.find('li a').removeClass('button-primary');
				$(this).addClass('button-primary');

				widget_content.find('.tp_event_admin_widget').removeClass('active');
				widget_content.find('.tp_event_admin_widget[data-status="' + tab_content + '"]').addClass('active');
				return false;
			});
		}

		$('input[name="thimpress_events_email_enable"]').on('click', function () {
			var toggle = !($(this).is(':checked'));
			$('.email-setting-form-name, .email-setting-email-form, .email-setting-subject').toggleClass('hide-if-js', toggle);
		});

		$('input[name="thimpress_events_paypal_enable"]').on('click', function () {
			var toggle = !($(this).is(':checked'));
			$('.paypal-production-email, .paypal-sandbox-mode, .paypal-sandbox-email').toggleClass('hide-if-js', toggle);
		});

		$('input[name="thimpress_events_allow_register_event"]').on('click', function () {
			var toggle = !($(this).is(':checked'));
			$('.setting-currency, .setting-currency-position, .setting-currency-thousand, .setting-currency-separator, .setting-number-decimals').toggleClass('hide-if-js', toggle);
		});

		$(document).on('click', '.tp-event-dismiss-notice button', function (event) {
			var parent = $(this).closest('.tp-event-dismiss-notice');
			if (parent.length) {
				event.preventDefault();
				$.ajax({
					url : ajaxurl,
					type: 'POST',
					data: {
						action: 'event_remove_notice'
					}
				})
			}
		});


		TP_Event_Admin.admin_meta_boxes.init();
	};

	// event meta boxes
	TP_Event_Admin.admin_meta_boxes = {
		init          : function () {
			var _doc = $(document);
			_doc.on('click', '.event_meta_panel .open-extra', this.open_extra);
			this.datetimepicker();
		},
		open_extra    : function (e) {
			e.preventDefault();
			var _this = $(this),
				_input_target = $('#' + _this.attr('data-target')),
				_group = _input_target.parents('.option_group:first'),
				_data_text = _this.attr('data-text'),
				_text = _this.text();

			if (_input_target.val() === 'yes') {
				_group.addClass('hide-if-js');
				_input_target.val('');
			} else {
				_group.removeClass('hide-if-js');
				_input_target.val('yes');
			}

			_this.attr('data-text', _text).text(_data_text);
		},
		datetimepicker: function () {
			var _date_from = $('#_date_start'),
				_time_from = $('#_time_start'),
				_date_end = $('#_date_end'),
				_time_end = $('#_time_end'),
				_registration_end_date = $('#_registration_end_date'),
				_registration_end_time = $('#_registration_end_time');

			_date_from.datetimepicker({
				timepicker: false,
				format    : 'Y-m-d',
				onShow    : function (ct) {
					this.setOptions({
						maxDate: _date_end.val() ? _date_end.val() : false
					});
				}
			});
			_time_from.datetimepicker({
				datepicker: false,
				format    : 'H:i'
			});
			_date_end.datetimepicker({
				timepicker: false,
				format    : 'Y-m-d',
				setDate   : '+1',
				onShow    : function (ct) {
					this.setOptions({
						minDate: _date_from.val() ? _date_from.val() : false
					});
				}
			});
			_time_end.datetimepicker({
				datepicker: false,
				format    : 'H:i'
			});

			_registration_end_date.datetimepicker({
				timepicker: false,
				format    : 'Y-m-d',
				setDate   : '+1',
				onShow    : function (ct) {
					this.setOptions({
						minDate: 0
					});
				}
			});
			_registration_end_time.datetimepicker({
				datepicker: false,
				format    : 'H:i'
			});
		}

	};

	$(document).ready(function () {
		TP_Event_Admin.init();
	});
})(jQuery);