/**
 * UserRegistrationProAdmin JS
 */
jQuery(function ($) {
	var UR_ADDON = {
		init: function () {
			this.initialize_conditional_logic_settings();
			this.initialize_prevent_active_login_settings();
		},
		initialize_conditional_logic_settings: function () {
			/**
			 * Replace input field according to selected field in list field such as country, select etc.
			 */
			$(document).on(
				"change",
				".ur-conditional-wrapper .ur_conditional_field",
				function () {
					UR_ADDON.replaceFieldValues(
						".ur-conditional-wrapper",
						this
					);
				}
			);

			/**
			 * Default Hide show conditional logic container according to enable field.
			 */
			$(document)
				.find(".ur_use_conditional_logic_wrapper")
				.each(function () {
					var wrapper = $(this).closest(
						".ur_conditional_logic_container"
					);
					if (
						$(this).find("#ur_use_conditional_logic").is(":checked")
					) {
						wrapper.find(".ur_conditional_logic_wrapper").show();
					} else {
						wrapper.find(".ur_conditional_logic_wrapper").hide();
					}
				});

			/**
			 * Hide show conditional logic container on change of enable field.
			 */
			$(document).on("change", "#ur_use_conditional_logic", function () {
				var wrapper = $(this).closest(
					".ur_conditional_logic_container"
				);

				if ($(this).is(":checked")) {
					wrapper.find(".ur_conditional_logic_wrapper").show();
				} else {
					wrapper.find(".ur_conditional_logic_wrapper").hide();
				}
			});
		},
		/**
		 * Toggle a field with reference to other field's value.
		 */
		settings_fields_toggler: function (setting, toggle_field) {
			if (setting.is(":checked")) {
				toggle_field.show();
			} else {
				toggle_field.hide();
			}
		},
		/**
		 * Replace Input field with dropdown according to fields like checkbox, select, country, etc.
		 */
		replaceFieldValues: function ($class, $this) {
			var data_type = $("option:selected", $this).attr("data-type");
			var selected_val = $("option:selected", $this).val();
			var input_node = $($this)
				.closest($class)
				.find(".ur-conditional-input");

			//Grab input node attributes
			var nodeName = input_node.attr("name"),
				nodeClass = input_node.attr("class");

			if (
				data_type == "checkbox" ||
				data_type == "radio" ||
				data_type == "select" ||
				data_type == "country" ||
				data_type == "billing_country" ||
				data_type == "shipping_country" ||
				data_type == "select2" ||
				data_type == "multi_select2" ||
				data_type == "multi_choice"
			) {
				if (
					data_type == "select" ||
					data_type == "select2" ||
					data_type == "multi_select2"
				) {
					var values = $(
						'.ur-selected-inputs .ur-selected-item .ur-general-setting-field-name input[value="' +
							selected_val +
							'"]'
					)
						.closest(".ur-selected-item")
						.find(".ur-field option")
						.map(function () {
							return $(this).val();
						});
				} else if (
					data_type == "country" ||
					data_type == "billing_country" ||
					data_type == "shipping_country"
				) {
					var countryKey = $(
						'.ur-selected-inputs .ur-selected-item .ur-general-setting-field-name input[value="' +
							selected_val +
							'"]'
					)
						.closest(".ur-selected-item")
						.find(".ur-field option")
						.map(function () {
							return $(this).val();
						});
					var countryName = $(
						'.ur-selected-inputs .ur-selected-item .ur-general-setting-field-name input[value="' +
							selected_val +
							'"]'
					)
						.closest(".ur-selected-item")
						.find(".ur-field option")
						.map(function () {
							return $(this).text();
						});
				} else {
					var values = $(
						'.ur-selected-inputs .ur-selected-item .ur-general-setting-field-name input[value="' +
							selected_val +
							'"]'
					)
						.closest(".ur-selected-item")
						.find(".ur-field input")
						.map(function () {
							return $(this).val();
						});
				}
				var options = "<option value>--select--</option>";

				if (
					data_type == "country" ||
					data_type == "billing_country" ||
					data_type == "shipping_country"
				) {
					var countries = $(
						'.ur-general-setting-field-name input[value="' +
							selected_val +
							'"'
					)
						.closest(".ur-selected-item")
						.find(
							".ur-advance-selected_countries select option:selected"
						);
					var options_html = [];

					$(this)
						.find(".urcl-value select")
						.html('<option value="">--select--</option>');
					countries.each(function () {
						var country_iso = $(this).val();
						var country_name = $(this).text();

						options_html.push(
							'<option value="' +
								country_iso +
								'">' +
								country_name +
								"</option>"
						);
					});
					options = options_html.join("");
				} else {
					if (values.length == 1 && values[0] === "") {
						options =
							'<option value="1">' +
							urcl_data.checkbox_checked +
							"</option>";
					} else {
						$(values).each(function (index, el) {
							options =
								options +
								'<option value="' +
								el +
								'">' +
								el +
								"</option>";
						});
					}
				}

				input_node.replaceWith(
					'<select name="' +
						nodeName +
						'" class="' +
						nodeClass +
						'">' +
						options +
						"</select>"
				);
			} else {
				input_node.replaceWith(
					'<input type="text" name="' +
						nodeName +
						'" class="' +
						nodeClass +
						'">'
				);
			}
		},
		/**
		 * Handle prevent active login settings.
		 */
		initialize_prevent_active_login_settings: function () {
			$("#user_registration_pro_general_setting_prevent_active_login").on(
				"click",
				function () {
					UR_ADDON.showHideActiveLogin($(this));
				}
			);

			UR_ADDON.showHideActiveLogin(
				$("#user_registration_pro_general_setting_prevent_active_login")
			);
		},
		/**
		 * Show or hide active login limits option.
		 */
		showHideActiveLogin: function ($node) {
			if ($node.prop("checked")) {
				$("#user_registration_pro_general_setting_limited_login")
					.parents("tr")
					.removeClass("userregistration-forms-hidden");
			} else {
				$("#user_registration_pro_general_setting_limited_login")
					.parents("tr")
					.addClass("userregistration-forms-hidden");
			}
		},
	};

	UR_ADDON.init();
});
