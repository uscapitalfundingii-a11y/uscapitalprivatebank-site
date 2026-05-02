<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (!empty($uscap_dashboard_stats)) { ?>
<div class="col-md-12 mtop20 uscap-dashboard-overview">
    <div class="panel_s">
        <div class="panel-body">
            <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-gap-2">
                <div>
                    <h4 class="tw-mt-0 tw-mb-1 tw-font-semibold">CRM Command Overview</h4>
                    <p class="text-muted tw-mb-0">Live operating shortcuts for customer service, onboarding, and account support.</p>
                </div>
                <a href="<?php echo admin_url('home'); ?>" class="btn btn-default btn-sm">
                    <i class="fa fa-bolt menu-icon"></i> Light control panel
                </a>
            </div>
            <div class="row tw-mt-5">
                <?php foreach ($uscap_dashboard_stats as $stat) { ?>
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-2 tw-mb-3">
                        <a href="<?php echo e($stat['href']); ?>" class="uscap-dashboard-card uscap-dashboard-card-<?php echo e($stat['tone']); ?>">
                            <span class="uscap-dashboard-card-icon">
                                <i class="fa <?php echo e($stat['icon']); ?>"></i>
                            </span>
                            <span class="uscap-dashboard-card-value"><?php echo e($stat['value']); ?></span>
                            <span class="uscap-dashboard-card-label"><?php echo e($stat['label']); ?></span>
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<style>
.uscap-dashboard-card {
    display: block;
    min-height: 126px;
    padding: 18px 16px;
    border: 1px solid #dce6f5;
    border-radius: 8px;
    background: #fff;
    color: #1f2937;
    text-decoration: none;
    transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
}
.uscap-dashboard-card:hover,
.uscap-dashboard-card:focus {
    color: #111827;
    border-color: #1d4ed8;
    box-shadow: 0 8px 20px rgba(29, 78, 216, .12);
    transform: translateY(-1px);
    text-decoration: none;
}
.uscap-dashboard-card-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    margin-bottom: 12px;
    background: #eff6ff;
    color: #1d4ed8;
}
.uscap-dashboard-card-value {
    display: block;
    font-size: 24px;
    line-height: 1.2;
    font-weight: 700;
    color: #0f172a;
}
.uscap-dashboard-card-label {
    display: block;
    margin-top: 5px;
    color: #64748b;
    font-size: 12px;
    line-height: 1.35;
    text-transform: uppercase;
}
.uscap-dashboard-card-danger .uscap-dashboard-card-icon { background: #fef2f2; color: #dc2626; }
.uscap-dashboard-card-warning .uscap-dashboard-card-icon { background: #fff7ed; color: #ea580c; }
.uscap-dashboard-card-success .uscap-dashboard-card-icon { background: #ecfdf5; color: #059669; }
.uscap-dashboard-card-info .uscap-dashboard-card-icon { background: #ecfeff; color: #0891b2; }
.uscap-dashboard-card-primary .uscap-dashboard-card-icon { background: #eff6ff; color: #1d4ed8; }
</style>
<?php } ?>
