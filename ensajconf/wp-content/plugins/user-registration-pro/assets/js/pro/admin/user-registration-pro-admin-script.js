/**
 * UserRegistrationProAdmin JS
 * global user_registration_pro_admin_script_data
 */
jQuery(function ($) {
	var UR_PRO = {
		init: function () {
			this.initalize_form_reset_settings();
			this.initalize_whitelist_domain_settings();
			this.initalize_form_auto_password_generation_settings();
			this.initialize_conditional_logic_settings();
			this.initialize_extension_page();
			this.initialize_external_fields_mapping();
			this.init_all_tooltips();
			this.manage_tooltip_fields();
			this.init_export_custom_fields();
			this.init_render_dynamic_form_fields();
		},
		/**
		 * Initialize all tooltip elements.
		 */
		init_all_tooltips: function () {
			var args = {
				theme: "tooltipster-borderless",
				maxWidth: 200,
				multiple: true,
				interactive: true,
				position: "bottom",
				contentAsHTML: true,
				functionInit: function (instance, helper) {
					var $origin = jQuery(helper.origin),
						dataTip = $origin.attr("data-tip");

					if (dataTip) {
						instance.content(dataTip);
					}
				},
			};

			$(".user-registration-help-tip").tooltipster(args);
		},
		/**
		 * Initialize External Plugin fields mapping.
		 */
		initialize_external_fields_mapping: function () {
			$(document).ready(function () {
				// Save payment condition to $_POST
				$(document).on(
					"user_registration_admin_before_form_submit",
					function (event, data) {
						var external_mapping_settings =
							UR_PRO.save_external_mapping_settings();
						if (external_mapping_settings.length > 0) {
							data.data["ur_pro_external_mapping_settings"] =
								external_mapping_settings;
						}
					}
				);
				UR_PRO.hide_show_external_fields_mapping();

				$(document).on(
					"change",
					"#user_registration_enable_external_fields_mapping_field",
					function () {
						UR_PRO.hide_show_external_fields_mapping();
					}
				);
				$(document).on(
					"change",
					".ur-pro-db-table-section",
					function () {
						UR_PRO.hide_show_external_table_selection();
					}
				);
				var ur_pro_form_fields =
					user_registration_pro_admin_script_data.ur_pro_form_fields;

				$(document).on(
					"click",
					".ur-pro-external-field-map-group .add",
					function () {
						var $data_key = $(this)
							.closest(".ur-pro-field-mapping-box")
							.attr("data-last-key");
						$data_key++;

						var $output =
							'<li class="ur-pro-external-field-map-group" data-key="' +
							$data_key +
							'">';
						$output +=
							'<div class="ur-pro-external-field-map-form-group">';
						$output +=
							'<select class="ur-pro-fields ur-pro-field-map-select" name="ur_pro_external_map_form_fields[' +
							$data_key +
							']">';
						$output +=
							'<option value="">-- Select Field --</option>';
						$output += UR_PRO.get_fields_list();
						$output += "</select></div>";
						$output +=
							'<div class="ur-pro-operator"> <i class="dashicons dashicons-arrow-right-alt"></i> </div>';
						$output += '<div class="ur-pro-value">';
						$output +=
							'<input name="user_registration_form_value[' +
							$data_key +
							']" class="ur_pro_external_field_name" placeholder="Enter Field Key" type="text" />';
						$output += "</div>";
						$output += '<span class="add">';
						$output += '<i class="dashicons dashicons-plus"></i>';
						$output += "</span>";
						$output += '<span class="remove">';
						$output += '<i class="dashicons dashicons-minus"></i>';
						$output += "</span></li>";

						$(this)
							.closest(".ur-pro-field-mapping-box")
							.append($output);
						$(this)
							.closest(".ur-pro-field-mapping-box")
							.attr("data-last-key", $data_key);
					}
				);

				$(document).on(
					"click",
					".ur-pro-external-field-map-group .remove",
					function () {
						var row_count = $(".ur-pro-field-mapping-box").find(
							".ur-pro-external-field-map-group"
						).length;
						if (row_count > 1) {
							$(this).parent().remove();
						}
					}
				);

				$(document).on("change", ".ur_pro_db_table", function () {
					$table = $(this).val();
					$(".ur-spinner").remove();
					UR_PRO.retrive_db_table_columns($table);
				});
			});
		},
		/**
		 * Initialize whitelist domain form settings in form builder.
		 */
		initalize_whitelist_domain_settings: function () {
			$(document).ready(function () {
				var enable_whitelist_domain_field = $(
						"#user_registration_form_setting_enable_whitelist_domain"
					),
					whitelist_domain_field = $(
						"#user_registration_form_setting_whitelist_domain_field"
					),
					whitelist_domain_entries_field = $(
						"#user_registration_form_setting_domain_restriction_settings_field"
					);

				UR_PRO.settings_fields_toggler(
					enable_whitelist_domain_field,
					whitelist_domain_field
				);
				UR_PRO.settings_fields_toggler(
					enable_whitelist_domain_field,
					whitelist_domain_entries_field
				);

				// Check if enable search form is checked to hide/show search criteria settings div.
				enable_whitelist_domain_field.on("change", function () {
					UR_PRO.settings_fields_toggler(
						$(this),
						whitelist_domain_field
					);
					UR_PRO.settings_fields_toggler(
						$(this),
						whitelist_domain_entries_field
					);
				});
			});
		},
		/**
		 * Initialize reset form settings in form builder.
		 */
		initalize_form_reset_settings: function () {
			$(document).ready(function () {
				var form_reset_field = $(
						"#user_registration_form_setting_enable_reset_button"
					),
					form_reset_class_field = $(
						"#user_registration_form_setting_form_reset_class_field"
					),
					form_reset_label_field = $(
						"#user_registration_form_setting_form_reset_label_field"
					);

				UR_PRO.settings_fields_toggler(
					form_reset_field,
					form_reset_class_field
				);
				UR_PRO.settings_fields_toggler(
					form_reset_field,
					form_reset_label_field
				);

				// Check if enable search form is checked to hide/show search criteria settings div.
				form_reset_field.on("change", function () {
					UR_PRO.settings_fields_toggler(
						$(this),
						form_reset_class_field
					);
					UR_PRO.settings_fields_toggler(
						$(this),
						form_reset_label_field
					);
				});
			});
		},
		initalize_form_auto_password_generation_settings: function () {
			$(document).ready(function () {
				var auto_password_activate = $(
						"#user_registration_pro_auto_password_activate"
					),
					auto_generated_password_length_field = $(
						"#user_registration_pro_auto_generated_password_length_field"
					);

				UR_PRO.settings_fields_toggler(
					auto_password_activate,
					auto_generated_password_length_field
				);

				// Check if enable search form is checked to hide/show search criteria settings div.
				auto_password_activate.on("change", function () {
					UR_PRO.settings_fields_toggler(
						$(this),
						auto_generated_password_length_field
					);
				});
			});
		},
		initialize_conditional_logic_settings: function () {
			/**
			 * Replace input field according to selected field in list field such as country, select etc.
			 */
			$(document).on(
				"change",
				".ur-conditional-wrapper .ur_conditional_field",
				function () {
					UR_PRO.replaceFieldValues(".ur-conditional-wrapper", this);
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
		 * Hide Show External Table Selection according to user selection on the dropdown.
		 */
		hide_show_external_table_selection: function () {
			$selected_option = $(".ur-pro-db-table-section").val();
			if ("external_table" === $selected_option) {
				UR_PRO.init_all_tooltips();
				$(".ur-pro-external-field-table-column-selection").show();
			} else {
				$(".ur_pro_db_table").val(
					user_registration_pro_admin_script_data.ur_pro_db_prefix +
						"usermeta"
				);
				$(".ur_pro_user_id_db_column").val("");
				$(".ur_pro_field_key_db_column").val("");
				$(".ur_pro_field_value_db_column").val("");
				$(".ur-pro-external-field-table-column-selection").hide();
			}
		},
		/**
		 * Hide Show External Field Mapping Section when enabled disabled.
		 */
		hide_show_external_fields_mapping: function () {
			var enable_assign_user_role = $(
				"#user_registration_enable_external_fields_mapping"
			).is(":checked");

			if (enable_assign_user_role) {
				if ($(".ur-pro-fields-mapping-container").length === 0) {
					var $html = $(
						user_registration_pro_admin_script_data.ur_pro_external_fields_mapping_output
					);
					var $fields_list = $html.find(".ur-pro-field-map-select");
					var $output =
						'<option value="">-- Select Field --</option>' +
						UR_PRO.get_fields_list();
					$fields_list.each(function () {
						var selected_value = $(this).val();
						$(this).html($output);
						$(this).val(selected_value);
						$(this)
							.find('option[value="' + selected_value + '"]')
							.prop("selected", true);
					});

					$(
						"#user_registration_enable_external_fields_mapping_field"
					).after($html);
					UR_PRO.hide_show_external_table_selection();
				} else {
					$(".ur-pro-fields-mapping-container").show();
					UR_PRO.hide_show_external_table_selection();
				}
			} else {
				$(".ur-pro-fields-mapping-container").hide();
			}
		},
		/**
		 * Get list of fields wrapped in `option` tag.
		 * Warning: The output will NOT be wrapped with the `select` tag.
		 */
		get_fields_list: function () {
			var output = "";
			$(".ur-grid-lists .ur-selected-item .ur-general-setting").each(
				function () {
					var field_label = $(this)
						.closest(".ur-selected-item")
						.find(" .ur-admin-template .ur-label label")
						.text();
					var field_key = $(this)
						.closest(".ur-selected-item")
						.find(" .ur-admin-template .ur-field")
						.data("field-key");

					//strip certain fields
					if (
						"section_title" == field_key ||
						"html" == field_key ||
						"wysiwyg" == field_key ||
						"billing_address_title" == field_key ||
						"shipping_address_title" == field_key
					) {
						return;
					}

					var field_name = $(this)
						.find("[data-field='field_name']")
						.val();

					if (typeof field_name !== "undefined") {
						output +=
							'<option value="' +
							field_name +
							'" data-type="' +
							field_key +
							'">' +
							field_label +
							"</option>";
					}
				}
			);
			return output;
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
					data_type == "radio" ||
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
		 * Get Columns list by Table.
		 * @param {string} $table Table Name
		 */
		retrive_db_table_columns: function ($table) {
			var data = {
				action: "user_registration_pro_get_db_columns_by_table",
				security:
					user_registration_pro_admin_script_data.ur_pro_get_db_columns_by_table,
				table: $table,
			};

			$(".user_registration_user_id_db_column_label").append(
				'<span class="ur-spinner"></span>'
			);
			$(".user_registration_field_key_db_column_label").append(
				'<span class="ur-spinner"></span>'
			);
			$(".user_registration_field_value_db_column_label").append(
				'<span class="ur-spinner"></span>'
			);

			$.ajax({
				url: user_registration_pro_admin_script_data.ajax_url,
				data: data,
				type: "POST",
				success: function (response) {
					if (response.success) {
						if ("undefined" != typeof response.data.columns) {
							var columns_list = JSON.parse(
								response.data.columns
							);

							$(".ur_pro_user_id_db_column option").remove();
							$(".ur_pro_field_key_db_column option").remove();
							$(".ur_pro_field_value_db_column option").remove();

							$(".ur_pro_user_id_db_column").append(
								$("<option>", {
									value: "",
									text: "-- Select Column for User ID --",
								})
							);
							$(".ur_pro_field_key_db_column").append(
								$("<option>", {
									value: "",
									text: "-- Select Column for Field Key --",
								})
							);
							$(".ur_pro_field_value_db_column").append(
								$("<option>", {
									value: "",
									text: "-- Select Column for Field Value --  ",
								})
							);

							var $output_options = "";
							$.each(columns_list, function (key, value) {
								$output_options +=
									'<option value="' +
									value +
									'">' +
									value +
									"</option>";
								$(".ur_pro_user_id_db_column").append(
									$("<option>", { value: value, text: value })
								);
								$(".ur_pro_field_key_db_column").append(
									$("<option>", { value: value, text: value })
								);
								$(".ur_pro_field_value_db_column").append(
									$("<option>", { value: value, text: value })
								);
							});
						}
					}
					$(".ur-spinner").remove();
				},
			});
		},
		/**
		 * save external mapping settings from form builder
		 */
		save_external_mapping_settings: function () {
			var form_data = [];
			var single_row = $(".ur-pro-field-mapping-wrap");

			$.each(single_row, function () {
				var grid_list_item = $(this).find(
					".ur-pro-external-field-map-group"
				);
				var all_field_data = [];
				var inner_mapped_fields = [];
				$.each(grid_list_item, function () {
					var mapped_fields = {
						ur_field: $(this)
							.find(".ur-pro-field-map-select")
							.val(),
						external_field: $(this)
							.find(".ur_pro_external_field_name")
							.val(),
					};
					inner_mapped_fields.push(mapped_fields);
				});
				all_field_data.push(inner_mapped_fields);
				var all_fields = {
					db_table: $(".ur_pro_db_table").val(),
					user_id_db_column: $(".ur_pro_user_id_db_column").val(),
					field_key_db_column: $(".ur_pro_field_key_db_column").val(),
					field_value_db_column: $(
						".ur_pro_field_value_db_column"
					).val(),
					mapped_fields: all_field_data,
				};
				form_data.push(all_fields);
			});
			return form_data;
		},
		initialize_extension_page: function () {
			$(".ur_addons_wrap")
				.find(".install-now")
				.on("click", function (event) {
					var this_node = $(this),
						data = {
							action: "user_registration_pro_extension_install",
							security:
								user_registration_pro_admin_script_data.ur_pro_install_extension,
							slug: this_node.data("slug"),
							name: this_node.data("name"),
						};

					event.preventDefault();

					this_node.text("Installing...");
					this_node.append('<span class="ur-spinner"></span>');
					$.ajax({
						url: user_registration_pro_admin_script_data.ajax_url,
						data: data,
						type: "POST",
						success: function (response) {
							var notice_type = response.success
									? "notice-success"
									: "notice-error",
								notice =
									'<div class="notice ' +
									notice_type +
									'"><p>' +
									response.message +
									"</p></div>";

							$(notice).insertBefore(
								this_node
									.closest(".ur_addons_wrap")
									.find(".subsubsub")
							);

							if (response.success) {
								this_node.text("Installed");

								if (response.activateUrl) {
									this_node.text("Activate");
									this_node
										.removeClass("install-now")
										.addClass("activate-now button-primary")
										.attr("href", response.activateUrl);

									this_node
										.closest(".ur-plugin-card-bottom")
										.find(".status-label")
										.removeClass("status-install-now")
										.addClass("status-inactive")
										.text("Inactive");
								}
							} else {
								this_node
									.text(
										user_registration_pro_admin_script_data.ur_pro_extension_installed_failed_text
									)
									.prop("disabled", true);
							}

							$(".ur-spinner").remove();
							this_node.off("click");
						},
					});
				});
		},

		/**
		 * Initialize Tooltip Enanbled Fields
		 */
		manage_tooltip_fields: function () {
			$('select[data-field="tooltip"]').each(function () {
				if ($(this).val() === "yes") {
					$(this)
						.closest(".ur-selected-item")
						.find(".ur-label")
						.find("label")
						.append('<span class="ur-portal-tooltip"></span>');

					// Toggle Tooltip Message Field
					$(this)
						.closest(".ur-toggle-content")
						.find(".ur-general-setting-tooltip-message")
						.show(500);
				} else {
					$(this)
						.closest(".ur-toggle-content")
						.find(".ur-general-setting-tooltip-message")
						.hide(500);
				}

				$(document).on("click", ".ur-selected-item", function (e) {
					e.stopImmediatePropagation();

					var general_setting = $(".ur-general-setting-field");

					$.each(general_setting, function () {
						var $this_obj = $(this);
						switch ($this_obj.attr("data-field")) {
							case "tooltip":
								UR_PRO.trigger_general_setting_tooltip(
									$this_obj
								);
								$this_obj.on("change", function () {
									UR_PRO.trigger_general_setting_tooltip(
										$this_obj
									);
								});
								break;

							case "tooltip_message":
								$this_obj.on("input", function () {
									UR_PRO.trigger_general_setting_tooltip_message(
										$this_obj
									);
								});
								break;
						}
					});
				});
			});
		},

		/**
		 * Reflects changes in enable tooltip field of field settings
		 * into selected field in form builder area.
		 *
		 * @param object $label Tooltip field of fields from field settings.
		 */
		trigger_general_setting_tooltip: function ($label) {
			var wrapper = $(".ur-selected-item.ur-item-active"),
				selector_field_name = $label
					.closest("#ur-setting-form")
					.find("[data-field='field_name']")
					.val(),
				active_field_name = wrapper
					.find("[data-field='field_name']")
					.val();
			wrapper
				.find(".ur-general-setting-block")
				.find(
					'select[data-field="' +
						$label.attr("data-field") +
						'"] option'
				)
				.removeAttr("selected");

			if (selector_field_name === active_field_name) {
				if (
					wrapper.find(".ur-label").find(".ur-portal-tooltip")
						.length > 0
				) {
					wrapper
						.find(".ur-label")
						.find(".ur-portal-tooltip")
						.remove();
				}

				if ("yes" === $label.val()) {
					wrapper
						.find(".ur-label")
						.find("label")
						.append('<span class="ur-portal-tooltip"></span>');

					// Show Tooltip Message Field
					$label
						.closest(".ur-toggle-content")
						.find(".ur-general-setting-tooltip-message")
						.show(500);

					wrapper
						.find(".ur-general-setting-block")
						.find(
							'select[data-field="' +
								$label.attr("data-field") +
								'"]'
						)
						.find('option[value="' + $label.val() + '"]')
						.attr("selected", true);
				} else {
					wrapper
						.find(".ur-label")
						.find("label")
						.find("span.ur-portal-tooltip")
						.remove();

					// Hide Tooltip Message Field
					$label
						.closest(".ur-toggle-content")
						.find(".ur-general-setting-tooltip-message")
						.hide(500);
				}
			}
		},

		/**
		 * Reflects changes in tooltip message field of field settings into selected field in form builder area.
		 *
		 * @param object $label Tooltip field message fields from field settings.
		 */
		trigger_general_setting_tooltip_message: function ($label) {
			var wrapper = $(".ur-selected-item.ur-item-active");
			wrapper
				.find(".ur-general-setting-block")
				.find(
					'textarea[data-field="' + $label.attr("data-field") + '"]'
				)
				.val($label.val());
		},
		/**
		 * Export custom field handler
		 *
		 * @since 3.1.3
		 */
		init_export_custom_fields: function () {
			var form_selector = $("select.ur-input.forms-list");
			form_selector.on("change", function () {
				var ur_spinner = $('<span class="ur-spinner"></span>');
				$(".ur-export-custom-fields > p").append(ur_spinner);
				var form_id = $(this).val();
				$.ajax({
					url: user_registration_pro_admin_script_data.ajax_url,
					data: {
						action: "user_registration_pro_get_form_fields_list_by_form_id",
						form_id: form_id,
						security:
							user_registration_pro_admin_script_data.ur_pro_get_form_fields_by_form_id,
					},
					type: "post",
					success: function (response) {
						// Remove all fields except First Checkbox.
						$(".ur-form-fields-container")
							.contents()
							.slice(4)
							.remove();

						var fields_dict = JSON.parse(
							response.data.form_field_list
						);

						$.each(fields_dict, function (key, value) {
							var el = $(
								'<input type="checkbox" name="csv-export-custom-fields[]" class="ur-custom-fields-input" checked>'
							);

							el.val(key);

							$(".ur-form-fields-container").append(el);
							$(".ur-form-fields-container").append(
								"&nbsp;" + value + "<br />"
							);
						});
						$("input.ur-all-fields-option").prop("checked", true);
						$(".ur_export_csv_fields_dict").val(
							response.data.form_field_list
						);
						ur_spinner.remove();
					},
				});
			});

			// Check all fields when All Fields input is checked or vice versa.
			$("input.ur-all-fields-option").on("change", function () {
				if (true === $(this).prop("checked")) {
					$(".ur-custom-fields-input").each(function () {
						$(this).prop("checked", true);
					});
				} else {
					$(".ur-custom-fields-input").each(function () {
						$(this).prop("checked", false);
					});
				}
			});

			// Check or uncheck the value of All Fields input based on other fields.
			$(document).on("change", ".ur-custom-fields-input", function () {
				if ($(".ur-custom-fields-input:not(:checked)").length === 0) {
					$("input.ur-all-fields-option").prop("checked", true);
				} else {
					$("input.ur-all-fields-option").prop("checked", false);
				}
			});
		},

		// Render form field dynamically on drag and drop.
		init_render_dynamic_form_fields: function () {
			$(document).on(
				"focus",
				".ur_pro_fields_wrapper .column-form-fields select",
				function () {
					UR_PRO.replaceFormFields($(this));
				}
			);
		},

		replaceFormFields: function ($targetField) {
			var form_fields = {};
			$(".ur-grid-lists .ur-selected-item .ur-admin-template").each(
				function () {
					var field_label = $(this)
						.closest(".ur-selected-item")
						.find(" .ur-admin-template .ur-label label")
						.text();
					var field_key = $(this)
						.closest(".ur-selected-item")
						.find(" .ur-admin-template .ur-field")
						.data("field-key");
					var field_name = $(this)
						.parent()
						.find(".ur-general-setting-block")
						.find("input[data-field='field_name'")
						.val();
					var key = field_name + "." + field_key;

					//strip certain fields
					if (
						"section_title" == field_key ||
						"html" == field_key ||
						"wysiwyg" == field_key ||
						"billing_address_title" == field_key ||
						"shipping_address_title" == field_key
					) {
						return;
					}

					if (typeof field_key !== "undefined") {
						form_fields[key] = field_label;
					}
				}
			);

			var selected_value = $targetField.val();
			$targetField.empty();
			$targetField.html("<option value>Ignore this field</option>");
			$.each(form_fields, function (value, key) {
				$targetField.append(
					$("<option></option>").attr("value", value).text(key)
				);
			});
			$targetField
				.find("option[value='" + selected_value + "']")
				.attr("selected", "selected");
			$targetField.on("change", function () {
				selectedOption = $(this).val();
				UR_PRO.replaceFormFields($(this));
			});
			return form_fields;
		},
	};

	UR_PRO.init();
});
