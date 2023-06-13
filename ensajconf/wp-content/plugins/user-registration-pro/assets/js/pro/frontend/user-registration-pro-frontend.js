/**
 * UserRegistrationProPopup JS
 * global user_registration_pro_frontend_data
 * global user_registration_params
 */

"use strict";

(function ($) {
	// Hide the menu item for logged in users.
	$(document).ready(function () {
		if (
			user_registration_pro_frontend_data.has_create_user_capability &&
			user_registration_pro_frontend_data.is_user_logged_in
		) {
			$(document).find(".user-registration-modal-link").show();
		} else if (
			!user_registration_pro_frontend_data.has_create_user_capability &&
			user_registration_pro_frontend_data.is_user_logged_in
		) {
			$(document).find(".user-registration-modal-link").hide();
		}
	});

	// Change Cursor to pointer when modal link is hovered.
	$(document).on("hover", ".user-registration-modal-link", function (e) {
		$(".user-registration-modal-link").css("cursor", "pointer");
	});

	// When user clicks on the menu item open the popup.
	$(document).on("click", ".user-registration-modal-link", function (e) {
		e.preventDefault();
		var classes = $.map($(this)[0].classList, function (cls, i) {
			if (cls.indexOf("user-registration-modal-link-") === 0) {
				var popup_id = cls.replace("user-registration-modal-link-", "");

				$(".user-registration-modal-" + popup_id).each(function () {
					$(this).show();
				});

				// Add user-registration-modal-open class to body when popup is rendered on menu click.
				$(document.body).addClass("user-registration-modal-open");
			}
		});
	});

	// Catch submit event and store values in localStorage so that error can be shown in that specific login form.
	$(".ur-frontend-form.login").on("submit", function () {
		if ($(this).closest(".user-registration-modal").length) {
			var classes = $.map(
				$(this).closest(".user-registration-modal")[0].classList,
				function (cls, i) {
					if (cls.indexOf("user-registration-modal-") === 0) {
						var popup_id = cls.replace(
							"user-registration-modal-",
							""
						);

						var ur_popup_details = {};
						ur_popup_details["popup_id"] = popup_id;

						if (
							$(".user-registration-modal-" + popup_id).closest(
								".entry-content"
							).length
						) {
							ur_popup_details["inner_popup"] = true;
						} else {
							ur_popup_details["inner_popup"] = false;
						}

						localStorage.setItem(
							"ur_pro_popup",
							JSON.stringify(ur_popup_details)
						);
					}
				}
			);
		} else {
			localStorage.removeItem("ur_pro_popup");
		}
	});

	// Add user-registration-modal-open class to body when popup is rendered from shortcode.
	$(".user-registration-modal").ready(function () {
		if (
			$(".entry-content").find(".user-registration-modal").length &&
			0 ===
				$(".entry-content").find(".user-registration-popup-button")
					.length
		) {
			$(document.body).addClass("user-registration-modal-open");
		}

		var popup_details = JSON.parse(localStorage.getItem("ur_pro_popup"));

		if (popup_details && popup_details.popup_id) {
			var popup_id = popup_details.popup_id,
				popup_div = $(".user-registration-modal-" + popup_id);

			if ($(".entry-content").find(".ur-frontend-form.login").length) {
				$(".entry-content")
					.find(".ur-frontend-form.login")
					.each(function () {
						if (
							$(this)
								.closest("body")
								.find(".entry-content .user-registration-error")
								.length
						) {
							if (true === popup_details.inner_popup) {
								$(this)
									.closest("body")
									.find(".entry-content")
									.find(".user-registration-error")
									.prependTo(
										popup_div.find(".user-registration")
									);
							} else {
								$(this)
									.closest(".user-registration")
									.find(".user-registration-error")
									.prependTo(
										popup_div.find(".user-registration")
									);
							}
							$(this)
								.closest("body")
								.find(".user-registration-modal")
								.hide();
							popup_div.show();
						}
					});
			} else {
				$(".user-registration-modal")
					.find(".ur-frontend-form.login")
					.each(function () {
						if (
							$(this).siblings(".user-registration-error").length
						) {
							$(this)
								.closest(".user-registration")
								.find(".user-registration-error")
								.prependTo(
									popup_div.find(".user-registration")
								);
						}
						$(this)
							.closest("body")
							.find(".user-registration-modal")
							.hide();
						popup_div.show();
					});
			}

			localStorage.removeItem("ur_pro_popup");

			// Add user-registration-modal-open class to body when popup is rendered on menu click.
			$(document.body).addClass("user-registration-modal-open");
		}

		/**
		 * Compatibility for rendering recaptcha on popup.
		 *
		 * @since 1.0.7
		 */
		$(".user-registration-modal")
			.find(".ur-frontend-form.login")
			.each(function () {
				if ($(this).find("#ur-recaptcha-node").length > 0) {
					var popup_id = $(this)
						.closest(".user-registration-modal")
						.attr("class")
						.split(" ")[1]
						.replace("user-registration-modal-", "");

					$(this)
						.find("#node_recaptcha_login")
						.attr("id", "node_recaptcha_login_popup_" + popup_id);
				}
			});
	});

	// When the user clicks on <span> (x), close the modal
	$(document).on(
		"click",
		".user-registration-modal__close-icon, .user-registration-modal__backdrop",
		function () {
			$(this)
				.closest(".user-registration-modal")
				.css({ display: "none", opacity: "1" });

			// Remove user-registration-modal-open class from body when popup is closed.
			$(document.body).removeClass("user-registration-modal-open");
		}
	);

	/** Triggers when user click on delete account menu for deleting their own account.
	 *
	 * @since v1.0.4
	 */
	$(document).on(
		"click",
		".user-registration-MyAccount-navigation-link--delete-account",
		function () {
			// Code for Delete Account Feature
			var delete_account_option =
				user_registration_pro_frontend_data.delete_account_option;
			// var icon = '<i class="dashicons dashicons-trash"></i>';
			var title =
				'<span class="user-registration-swal2-modal__title">' +
				user_registration_pro_frontend_data.delete_account_popup_title;

			swal.fire({
				title: title,
				html: user_registration_pro_frontend_data.delete_account_popup_html,
				confirmButtonText:
					user_registration_pro_frontend_data.delete_account_button_text,
				confirmButtonColor: "#FF4149",
				showConfirmButton: true,
				showCancelButton: true,
				cancelButtonText:
					user_registration_pro_frontend_data.cancel_button_text,
				customClass: {
					container: "user-registration-swal2-container",
				},
				focusConfirm: false,
				showLoaderOnConfirm: true,
				preConfirm: function () {
					return new Promise(function (resolve) {
						var data = "";
						if ("prompt_password" === delete_account_option) {
							var password =
								Swal.getPopup().querySelector(
									"#password"
								).value;

							if (!password) {
								Swal.showValidationMessage(
									user_registration_pro_frontend_data.please_enter_password
								);

								Swal.hideLoading();

								$(".swal2-actions")
									.find("button")
									.prop("disabled", false);
							} else {
								data = {
									action: "user_registration_pro_delete_account",
									password: password,
								};
							}
						} else {
							data = {
								action: "user_registration_pro_delete_account",
							};
						}
						if ("" !== data) {
							$.ajax({
								url: user_registration_pro_frontend_data.ajax_url,
								method: "POST",
								data: data,
							}).done(function (response) {
								if (response.success) {
									Swal.fire({
										icon: "success",
										title: user_registration_pro_frontend_data.account_deleted_message,
										customClass:
											"user-registration-swal2-modal user-registration-swal2-modal--center",
										showConfirmButton: false,
										timer: 1000,
									}).then(function (result) {
										window.location.reload();
									});
								} else {
									Swal.showValidationMessage(
										response.data.message
									);

									Swal.hideLoading();

									$(".swal2-actions")
										.find("button")
										.prop("disabled", false);
								}
							});
						}
					});
				},
			});
			return false;
		}
	);

	/** Triggers when user tries to reset the form.
	 *
	 * @since v1.0.7
	 */
	$(document).on("click", ".ur-reset-button", function (e) {
		e.preventDefault();
		Swal.fire({
			html: user_registration_pro_frontend_data.clear_button_text,
			icon: "warning",
			confirmButtonColor: "#3085d6",
			cancelButtonColor: "#d33",
			confirmButtonText: "Yes",
			allowOutsideClick: false,
			showCancelButton: true,
			customClass: {
				container: "user-registration-swal2-container",
			},
		}).then(function (result) {
			if (result.value === true) {
				$(document).trigger("user_registration_frontend_reset_button");
				$(".ur-field-item.field-select2 select")
					.val(null)
					.trigger("change");
				$(".ur-field-item.field-multi_select2 select")
					.val(null)
					.trigger("change");
				$(".ur-frontend-form")
					.find("form.register")
					.validate()
					.resetForm();
				$(".user-registration-error").remove();
				$(".ur-frontend-form").find("form.register").trigger("reset");
			}
		});
	});

	/** Triggers when user tries to forcelogout.
	 *
	 * @since v1.0.0
	 */
	$(document).on("click", ".user-registartion-force-logout", function (e) {
		e.preventDefault();
		var user_id = $(".user-registartion-force-logout").data("user-id");
		var user_email = $(".user-registartion-force-logout").data("email");
		$.ajax({
			type: "POST",
			url: user_registration_pro_frontend_data.ajax_url,
			data: {
				action: "user_registration_pro_send_email_logout",
				user_email: user_email,
				user_id: user_id,
			},
			success: function (response) {
				$("#user-registration")
					.find(".user-registration-error")
					.remove();
				$("#user-registration").prepend(
					'<ul class="user-registration-message">' +
						"Email has been Sent Succesfully" +
						"</ul>"
				);
			},
		});
	});

	$(document).ready(function () {
		if (
			typeof $.fn.mailcheck === "undefined" ||
			!user_registration_pro_frontend_data.mailcheck_enabled
		) {
			return;
		}
		// Setup default domains for Mailcheck.
		if (user_registration_pro_frontend_data.mailcheck_domains.length > 0) {
			Mailcheck.defaultDomains = Mailcheck.defaultDomains.concat(
				user_registration_pro_frontend_data.mailcheck_domains
			);
		}

		// Setup default top level domains for Mailcheck.
		if (
			user_registration_pro_frontend_data.mailcheck_toplevel_domains
				.length > 0
		) {
			Mailcheck.defaultTopLevelDomains =
				Mailcheck.defaultTopLevelDomains.concat(
					user_registration_pro_frontend_data.mailcheck_toplevel_domains
				);
		}

		// mailcheck suggestion
		$(document).on("blur", ".ur-field-item .input-email", function () {
			var $el = $(this),
				id = $el.attr("id");

			$el.mailcheck({
				suggested: function (el, suggestion) {
					$("#" + id + "_suggestion").remove();
					var suggestion_msg =
						user_registration_pro_frontend_data.message_email_suggestion_fields.replace(
							"{suggestion}",
							'<a href="#" class="mailcheck-suggestion" data-id="' +
								id +
								'" title="' +
								user_registration_pro_frontend_data.message_email_suggestion_title +
								'">' +
								suggestion.full +
								"</a>"
						);
					$(el).after(
						'<label class="user-registration-error mailcheck-error" id="' +
							id +
							'_suggestion">' +
							suggestion_msg +
							"</label>"
					);
				},
				empty: function () {
					$("#" + id + "_suggestion").remove();
				},
			});
			// Apply Mailcheck suggestion.
			$(document).on(
				"click",
				".ur-field-item .mailcheck-suggestion",
				function (e) {
					var $el = $(this),
						id = $el.attr("data-id");
					e.preventDefault();
					$("#" + id).val($el.text());
					$el.parent().remove();
				}
			);
		});
	});

	// Add padding when display icon option enabled
	$(document).ready(function () {
		$(".input-wrapper").each(function () {
			if ($(this).children().length == 2) {
				var firstEl = $(this).children(":first");
				if (firstEl.hasClass("password-input-group")) {
					firstEl
						.children(":first")
						.addClass("ur-pro-input-icon-padding");
				} else {
					firstEl.addClass("ur-pro-input-icon-padding");
				}
			}
		});
	});

	$(document).ready(function () {
		enable_keyboard_friendly_form();
		render_tooltips();
		restrict_copy_paste();
	});

	// Keyboard Friendly Form
	function enable_keyboard_friendly_form() {
		if (
			"yes" ===
				user_registration_pro_frontend_data.keyboard_friendly_form_enabled ||
			"1" ===
				user_registration_pro_frontend_data.keyboard_friendly_form_enabled
		) {
			var ur_form = $("form.register");
			var ur_fields = ur_form.find(".ur-frontend-field");
			ur_fields.first().trigger("focus");

			$("body").on("keydown", function (e) {
				if (e.ctrlKey || e.metaKey) {
					if (13 === e.which) {
						e.preventDefault();
						ur_form.submit();
					}
				}

				if (ur_fields.last().is(":focus")) {
					if (9 === e.which) {
						e.preventDefault();
						$("form.register")
							.find(".ur-submit-button")
							.first()
							.trigger("focus");
					}
				} else if (
					$("form.register")
						.find(".ur-submit-button.button")
						.is(":focus")
				) {
					if (9 === e.which) {
						e.preventDefault();
						ur_fields.first().trigger("focus");
					}
				}
			});
		}
	}

	// Render Tooltips.
	function render_tooltips() {
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

		$(".ur-portal-tooltip").tooltipster(args);
	}

	// Restrict copy/paste on confirm email and confirm password fields.
	function restrict_copy_paste() {
		var fields =
			user_registration_pro_frontend_data.restrict_copy_paste_fields;
		if (Array.isArray(fields) && fields.length) {
			$(fields).each(function (i, field) {
				$("#" + field).on("cut copy paste", function (e) {
					e.preventDefault();
				});
			});
		}
		if ($("#password_2").hasClass("restrict-copy-paste")) {
			$("#password_2").on("cut copy paste", function (e) {
				e.preventDefault();
			});
		}
	}
})(jQuery);
