<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
.client-dashboard-hero {
	position: relative;
	overflow: hidden;
	border-radius: 24px;
	padding: 34px 36px;
	margin-bottom: 28px;
	color: #fff;
	background: linear-gradient(135deg, rgba(8,32,71,.96), rgba(20,92,180,.88)), url('https://uscapitalprivatebank.com/assets/img/hero-bg.jpg') center/cover no-repeat;
	box-shadow: 0 24px 50px rgba(7, 32, 70, 0.22);
}
.client-dashboard-hero:before {
	content: '';
	position: absolute;
	inset: 0;
	background: radial-gradient(circle at top right, rgba(255,255,255,.2), transparent 35%);
}
.client-dashboard-hero > * {
	position: relative;
	z-index: 1;
}
.client-dashboard-eyebrow {
	display: inline-block;
	letter-spacing: .18em;
	text-transform: uppercase;
	font-size: 12px;
	font-weight: 700;
	color: #bcd7ff;
	margin-bottom: 14px;
}
.client-dashboard-title {
	margin: 0 0 16px;
	font-size: 44px;
	line-height: 1.08;
	font-weight: 800;
	color: #fff;
}
.client-dashboard-copy {
	max-width: 780px;
	font-size: 17px;
	line-height: 1.75;
	color: rgba(255,255,255,.88);
	margin-bottom: 22px;
}
.client-dashboard-actions {
	display: flex;
	flex-wrap: wrap;
	gap: 12px;
}
.client-dashboard-actions .btn-default {
	background: rgba(255,255,255,.1);
	color: #fff;
	border-color: rgba(255,255,255,.35);
}
.client-dashboard-steps {
	display: grid;
	grid-template-columns: repeat(3, minmax(0, 1fr));
	gap: 14px;
	margin-top: 24px;
}
.client-dashboard-step {
	padding: 18px 18px 16px;
	border-radius: 16px;
	background: rgba(255,255,255,.12);
	backdrop-filter: blur(8px);
	border: 1px solid rgba(255,255,255,.14);
}
.client-dashboard-step strong {
	display: block;
	margin-bottom: 8px;
	color: #bcd7ff;
	text-transform: uppercase;
	letter-spacing: .08em;
	font-size: 12px;
}
.client-dashboard-step span {
	display: block;
	color: #fff;
	line-height: 1.6;
}
@media (max-width: 767px) {
	.client-dashboard-hero {
		padding: 26px 22px;
	}
	.client-dashboard-title {
		font-size: 34px;
	}
	.client-dashboard-steps {
		grid-template-columns: 1fr;
	}
}
</style>

<div class="kt-grid__item kt-grid__item--fluid kt-grid kt-grid--ver kt-grid--stretch">
	<div class="kt-container kt-body  kt-grid kt-grid--ver" id="kt_body">
		<div class="kt-grid__item kt-grid__item--fluid kt-grid kt-grid--hor">

			<!-- begin:: Subheader -->
			<div class="kt-subheader   kt-grid__item" id="kt_subheader">
				<div class="kt-subheader__main">
					<h3 class="kt-subheader__title" id="greeting"></h3>
					<div class="kt-subheader__breadcrumbs">
						<a href="#" class="kt-subheader__breadcrumbs-home"><i class="flaticon2-shelter"></i></a>
						<span class="kt-subheader__breadcrumbs-separator"></span>
						<a href="" class="kt-subheader__breadcrumbs-link">
							Dashboard </a>
							<!-- <span class="kt-subheader__breadcrumbs-separator"></span> -->
							<!-- <a href="" class="kt-subheader__breadcrumbs-link">
							Default Dashboard </a> -->
						</div>
					</div>
					<div class="kt-subheader__toolbar">
							<div class="kt-subheader__wrapper">
							<a href="<?php echo site_url('clients/tickets'); ?>" class="btn kt-subheader__btn-secondary">
								<?php echo _l('support'); ?>
							</a>
							<div class="dropdown dropdown-inline" data-toggle="kt-tooltip" title="<?php echo _l('calendar'); ?>" data-placement="top">
								<a href="<?php echo site_url('clients/calendar'); ?>" class="btn btn-danger kt-subheader__btn-options" aria-haspopup="true" aria-expanded="false">
									<?php echo _l('calendar'); ?>
								</a>
								<div class="dropdown-menu dropdown-menu-right">
									<a class="dropdown-item" href="#"><i class="la la-plus"></i> New Product</a>
									<a class="dropdown-item" href="#"><i class="la la-user"></i> New Order</a>
									<a class="dropdown-item" href="#"><i class="la la-cloud-download"></i> New Download</a>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item" href="#"><i class="la la-cog"></i> Settings</a>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- end:: Subheader -->

				<div class="kt-container  kt-grid__item kt-grid__item--fluid" style="margin-bottom: 60px;">
					<div class="row">
						<div class="" style="width:100%;">
							<div class="kt-portlet" id="kt_portlet" style="padding-bottom:30px;">
								<div class="kt-portlet__body"><?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>
								<div class="client-dashboard-hero">
									<span class="client-dashboard-eyebrow">U.S. Capital Private Bank Client Portal</span>
									<h1 class="client-dashboard-title">Secure project management for every banking request.</h1>
									<p class="client-dashboard-copy">Use this portal to open a structured project, upload documents into the correct transaction workspace, and keep your communication with the bank organized, traceable, and professionally routed.</p>
									<div class="client-dashboard-actions">
										<a href="<?php echo site_url('clients/new_project'); ?>" class="btn btn-info">Need help? Try the wizard.</a>
										<a href="<?php echo site_url('clients/projects'); ?>" class="btn btn-default">View Projects</a>
										<a href="<?php echo site_url('clients/tickets'); ?>" class="btn btn-default">Support Tickets</a>
									</div>
									<div class="client-dashboard-steps">
										<div class="client-dashboard-step">
											<strong>Step 1</strong>
											<span>Create a project for the exact transaction or service request you are working on.</span>
										</div>
										<div class="client-dashboard-step">
											<strong>Step 2</strong>
											<span>Upload files and supporting documents directly inside that project workspace.</span>
										</div>
										<div class="client-dashboard-step">
											<strong>Step 3</strong>
											<span>Use tickets and updates inside the project so your relationship team has the full context.</span>
										</div>
									</div>
								</div>
								<div id="client-first-visit-help" style="display:none;">
									<?php $client_page_help_context = 'home'; ?>
									<?php get_template_part('client_directions'); ?>
								</div>
								<h4  style="color:#84c529;" class="projects-summary-heading no-mtop mbot15"><?php echo _l('projects_summary'); ?></h4>
								<br>
								<div class="row">
									<?php get_template_part('projects/project_summary'); ?>
								</div>

							</div>

						</div>
					</div>
				</div>
			</div>


			<div class="kt-container  kt-grid__item kt-grid__item--fluid" style="margin-bottom: 60px;">
				<div class="row">
					<div class="" style="width:100%">
						<div class="kt-portlet" id="kt_portlet" style="padding-bottom:30px;">
							<div class="kt-portlet__body"><?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>
							<h4 class="bold"><?php echo _l('clients_quick_invoice_info'); ?></h4>
							<a href="<?php echo site_url('clients/statement'); ?>"><?php echo _l('view_account_statement'); ?></a>
							<div class="row">

								<?php
								if(has_contact_permission('invoices')){ ?>
									<div class="panel-body" style="width:100%;">


										<hr />
										<?php get_template_part('invoices_stats'); ?>

										<div class="col-md-3 col-sm-12">

											<?php if(count($payments_years) > 0){ ?>
												<div class="form-group">
													<select data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" class="form-control" id="payments_year" name="payments_years" data-width="100%" onchange="total_income_bar_report();" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
														<?php foreach($payments_years as $year) { ?>
															<option value="<?php echo $year['year']; ?>"<?php if($year['year'] == date('Y')){echo 'selected';} ?>>
																<?php echo $year['year']; ?>
															</option>
														<?php } ?>
													</select>
												</div>
											<?php } ?>
											<?php if(is_client_using_multiple_currencies()){ ?>
												<div id="currency" class="form-group mtop15" data-toggle="tooltip" title="<?php echo _l('clients_home_currency_select_tooltip'); ?>">
													<select data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" class="form-control" name="currency">
														<?php foreach($currencies as $currency){
															$selected = '';
															if($currency['isdefault'] == 1){
																$selected = 'selected';
															}
															?>
															<option value="<?php echo $currency['id']; ?>" <?php echo $selected; ?>><?php echo $currency['symbol']; ?> - <?php echo $currency['name']; ?></option>
														<?php } ?>
													</select>
												</div>
											<?php } ?>

										</div>


										<div class="col-md-12">
											<div class="relative" style="max-height:400px;">
												<canvas id="client-home-chart" height="400" class="animated fadeIn"></canvas>
											</div>
										</div>

									<?php } ?>
								</div>
							</div>

						</div>

					</div>
				</div>
			</div>
		</div>




		<div class="kt-container  kt-grid__item kt-grid__item--fluid" style="margin-bottom: 60px;">
			<div class="row">
				<div class="" style="width:100%;">
					<div class="kt-portlet" id="kt_portlet">
						<div class="kt-portlet__body"><?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>

						<?php echo get_template_part('tickets'); ?>
					</div>

				</div>
			</div>
		</div>
	</div>






</div>





<!--End::Dashboard 4-->
</div>

<!-- end:: Content -->
</div>
</div>
</div>

<script>
var greetDate = new Date();
var hrsGreet = greetDate.getHours();

var greet;
if (hrsGreet < 12)
greet = "<?php echo _l('good_morning'); ?>,";
else if (hrsGreet >= 12 && hrsGreet <= 17)
greet = "<?php echo _l('good_afternoon'); ?>,";
else if (hrsGreet >= 17 && hrsGreet <= 24)
greet = "<?php echo _l('good_evening'); ?>,";

if(greet) {
	document.getElementById('greeting').innerHTML =
	'<b>' + greet + ' <?php echo $contact->firstname; ?>!</b>';
}

(function () {
	var helpKey = 'client-home-help-dismissed-<?php echo (int) $contact->id; ?>';
	var helpWrap = document.getElementById('client-first-visit-help');
	if (!helpWrap) {
		return;
	}

	if (!window.localStorage.getItem(helpKey)) {
		helpWrap.style.display = 'block';
	}

	var alertBox = helpWrap.querySelector('.alert');
	if (!alertBox) {
		return;
	}

	var closeButton = document.createElement('button');
	closeButton.type = 'button';
	closeButton.className = 'close';
	closeButton.setAttribute('aria-label', 'Close');
	closeButton.innerHTML = '&times;';
	closeButton.onclick = function () {
		window.localStorage.setItem(helpKey, '1');
		helpWrap.style.display = 'none';
	};
	alertBox.insertBefore(closeButton, alertBox.firstChild);
})();
</script>
