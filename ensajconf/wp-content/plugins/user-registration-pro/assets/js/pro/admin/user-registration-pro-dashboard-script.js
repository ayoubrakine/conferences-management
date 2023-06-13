/**
 * UserRegistrationProDashboard JS
 * global user_registration_pro_dashboard_script_data
 */

jQuery(function ($) {
	var URE_Dashboard = {
		init: function () {
			this.dashboard_load_event();
		},
		/**
		 * Sends the form and date-time data selected by the user to be processed through ajax call
		 * and receives datas and users chartjs library to draw charts.
		 *
		 * @since  1.0.0
		 *
		 */
		dashboard_load_event: function () {
			$(document).ready(function () {
				// Trigger change event to load all forms datas.
				$(".user-registration-pro-dashboard-select-form").trigger(
					"change"
				);

				// When Date switcher ( Day, Week and Month ) is clicked make clicked switch active.
				$(".user-registration-pro-date-switcher").on(
					"click",
					function () {
						// Check if the Date switcher is previously clicked.
						if ($(this).hasClass("is-active")) {
							return;
						} else {
							$(this).addClass("is-active");
							var switcher_class = $(this)
								.attr("class")
								.split(" ")[3];
							$(
								".user-registration-pro-date-switcher:not(." +
									switcher_class +
									")"
							).removeClass("is-active");
							$("#date-range-selector").val("");

							// Again trigger change event to sync and toggle forms data.
							$(
								".user-registration-pro-dashboard-select-form"
							).trigger("change");
						}
					}
				);

				var date_flatpickrs = {};

				$(".user-registration-pro-date-range-selector").on(
					"click",
					function () {
						var field_id = $(this).data("id");
						var date_flatpickr = date_flatpickrs[field_id];

						// Load a flatpicker for the field, if hasn't been loaded.
						if (!date_flatpickr) {
							date_flatpickr = $(this).flatpickr({
								mode: "range",
								disableMobile: true,
								dateFormat: "Y-m-d",
								static: true,
								onClose: function (
									selectedDates,
									dateString,
									instance
								) {
									// Remove active class from date-switcher and trigger change event only if a date range is selected.
									if (0 !== selectedDates.length) {
										$(
											".user-registration-pro-date-switcher"
										).removeClass("is-active");

										// Verify if the selected date is previously selected or not.
										if (
											dateString !==
											$(
												".user-registration-pro-date-checker"
											).val()
										) {
											$(
												".user-registration-pro-date-checker"
											).val(dateString);
											$(
												".user-registration-pro-dashboard-select-form"
											).trigger("change");
										}
									}
								},
							});
							date_flatpickrs[field_id] = date_flatpickr;
						}

						if (date_flatpickr) {
							date_flatpickr.open();
						}
					}
				);
			});

			// Start the loading process when change event triggers.
			$(".user-registration-pro-dashboard-select-form").on(
				"change",
				function () {
					// Set default selected date to Week in order to load weekly data as default.
					var selected_date = "Week";

					// Check if any of the date switcher is set to active by click event and set selected date.
					$(".user-registration-pro-date-switcher").each(function () {
						var date_range = $("#date-range-selector").val();
						if ("" !== date_range) {
							selected_date = date_range;
						} else {
							if ($(this).hasClass("is-active")) {
								selected_date = $(this).html();
							}
						}
					});

					var form_id = $(this).val();
					var data = {
						action: "user_registration_pro_dashboard_analytics",
						form_id: form_id,
						selected_date: selected_date,
					};

					$(".user-registration-pro-dashboard__body").html(
						'<div class="user-registration-card ur-text-center"><div class="user-registration-card__body"><span class="ur-spinner"></span></div></div>'
					);

					$.ajax({
						url: user_registration_pro_dashboard_script_data.ajax_url,
						data: data,
						type: "POST",
						success: function (response) {
							var message = response.data.message;
							var user_report = response.data.user_report;
							var date_range_data =
								user_report["weekly_data"]["daily_data"];
							$(".user-registration-pro-dashboard__body").html(
								message
							);

							// Draw specific registration source pie chart only if all forms is selected.
							if ("all" === form_id) {
								URE_Dashboard.specific_registration_source_chart(
									user_report
								);
							}
							var chart_canvas_area = $(
								"#user-registration-pro-registration-overview-chart-report-area"
							);

							var chart_options = {
								responsive: true,
								legend: {
									display: false,
								},
								scales: {
									xAxes: [
										{
											ticks: {
												maxTicksLimit: 10,
											},
										},
									],
								},
								legendCallback: function (chart) {
									var text = [];
									text.push(
										'<ul class="user-registration-pro-legend-' +
											chart.id +
											' ur-d-flex ur-flex-wrap ur-mt-3 ur-mb-0">'
									);

									for (var i = 0; i < 3; i++) {
										text.push(
											'<li><span class="user-registration-pro-color-tag" style="background-color:' +
												chart.data.datasets[i]
													.borderColor +
												'"></span>'
										);
										if (chart.data.datasets[i].label) {
											text.push(
												chart.data.datasets[i].label
											);
										}
										text.push("</li>");
									}
									text.push("</ul>");
									return text.join("");
								},
							};

							// Restructure the format of the data sent through ajax call to be compatible with chart's data requirements.
							var weekly_data = Object.keys(date_range_data),
								new_registration_datas = [],
								approved_users_datas = [],
								pending_users_datas = [],
								denied_users_datas = [];

							weekly_data.forEach(function (daily_data) {
								new_registration_datas.push(
									date_range_data[daily_data]
										.new_registration_in_a_day
								);
								approved_users_datas.push(
									date_range_data[daily_data]
										.approved_users_in_a_day
								);
								pending_users_datas.push(
									date_range_data[daily_data]
										.pending_users_in_a_day
								);
								denied_users_datas.push(
									date_range_data[daily_data]
										.denied_users_in_a_day
								);
							});

							var chart_data = {
								datasets: [
									{
										label: "New User Registration",
										fill: false,
										borderColor: "red", // The main line color
										data: new_registration_datas,
									},
									{
										label: "Approved Users",
										fill: false,
										borderColor: "blue",
										data: approved_users_datas,
									},
									{
										label: "Pending Users",
										fill: false,
										borderColor: "#800020",
										data: pending_users_datas,
									},
								],
								labels: weekly_data,
							};

							var chart = new Chart(chart_canvas_area, {
								type: "line",
								data: chart_data,
								options: chart_options,
							});

							$(".user-registration-total-registration-chart")
								.find(
									".user-registration-pro-registration-overview-chart-report-legends"
								)
								.html(chart.setDatasetVisibility(1, true));
						},
					});
				}
			);
		},
		/**
		 * Process the data sent through ajax call and
		 * draws pie chart for specific registration source data.
		 *
		 * @since  1.0.0
		 *
		 */
		specific_registration_source_chart: function (user_report) {
			var $user_report = user_report;
			var chart_canvas_area = $(
				"#user-registration-pro-specific-form-registration-overview-chart-report-area"
			);

			var chart_options = {
				responsive: true,
				legend: {
					display: false,
				},
				legendCallback: function (chart) {
					var text = [];
					text.push(
						'<ul class="user-registration-pro-legend-' +
							chart.id +
							' ur-d-flex ur-flex-wrap ur-mt-3 ur-mb-0">'
					);

					for (var i = 0; i < chart.data.labels.length; i++) {
						text.push(
							'<li><span class="user-registration-pro-color-tag" style="background-color:' +
								chart.data.datasets[0].backgroundColor[i] +
								'"></span>'
						);
						if (chart.data.labels[i]) {
							text.push(chart.data.labels[i]);
						}
						text.push("</li>");
					}
					text.push("</ul>");
					return text.join("");
				},
			};

			var specific_form_registration_data = Object.keys(
				$user_report["specific_form_registration"]
			).sort();

			var data_lable = [],
				data_value = [];

			specific_form_registration_data.forEach(function (daily_data) {
				data_lable.push($user_report["specific_form_registration"]);
				data_value.push(
					$user_report["specific_form_registration"][daily_data]
				);
			});

			var colors = [];
			for (var i = 0; i < specific_form_registration_data.length; i++) {
				colors.push(URE_Dashboard.getRandomColor());
			}

			var chart_data = {
				datasets: [
					{
						backgroundColor: colors, // The main line color
						data: data_value,
					},
				],
				labels: specific_form_registration_data,
			};

			var chart = new Chart(chart_canvas_area, {
				type: "doughnut",
				data: chart_data,
				options: chart_options,
			});

			$(".user-registration-specific-registration-chart")
				.find(
					".user-registration-pro-specific-form-registration-overview-chart-report-legends"
				)
				.html(chart.setDatasetVisibility(1, true));
		},
		/**
		 * Generate a random colour hex code to be used for filling chart areas.
		 *
		 * @since  1.0.0
		 *
		 */
		getRandomColor: function () {
			var letters = "0123456789ABCDEF";
			var color = "#";

			for (var i = 0; i < 6; i++) {
				color += letters[Math.floor(Math.random() * 16)];
			}

			return color;
		},
	};
	URE_Dashboard.init();

	// Toggles Registration form selection element based upon the popup type selected.
	$(".user-registration-pro-select-popup-type").ready(function () {
		var popup_type = $(
			"#select2-user_registration_pro_popup_type-container"
		).attr("title");

		if ("Login" === popup_type) {
			$(".single-registration-select").toggle();
		}
	});

	$(".user-registration-pro-select-popup-type").on("change", function () {
		var popup_type = $(
			"#select2-user_registration_pro_popup_type-container"
		).attr("title");

		if ("Login" === popup_type) {
			$(this)
				.closest("#mainform")
				.find(".single-registration-select")
				.toggle();
		} else {
			$(this)
				.closest("#mainform")
				.find(".single-registration-select")
				.toggle();
		}
	});
});
