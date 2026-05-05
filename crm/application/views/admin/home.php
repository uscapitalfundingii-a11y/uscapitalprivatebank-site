<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$dashboard = $dashboard ?? [];
$heroMetrics = $dashboard['hero_metrics'] ?? [];
$workload = $dashboard['workload'] ?? [];
$finance = $dashboard['finance'] ?? [];
$operations = $dashboard['operations'] ?? [];
$quickActions = $dashboard['quick_actions'] ?? [];

if (!function_exists('uscap_dashboard_percent')) {
    function uscap_dashboard_percent($value, $total)
    {
        $total = max((int) $total, 1);
        return min(100, max(0, round(((int) $value / $total) * 100)));
    }
}
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content uscap-crm-dashboard">
        <div class="uscap-dashboard-hero">
            <div class="uscap-hero-copy">
                <span class="uscap-eyebrow">US Capital Private Bank, ETO.</span>
                <h1>CRM Command Dashboard</h1>
                <p>One professional control surface for support, clients, leads, finance, projects, staff work, mailbox, chat, and appointments.</p>
            </div>
            <div class="uscap-hero-actions">
                <a href="<?php echo admin_url('tickets'); ?>" class="uscap-action-button uscap-action-primary">
                    <i class="fa fa-headset"></i> Support queue
                </a>
                <a href="<?php echo admin_url('clients'); ?>" class="uscap-action-button">
                    <i class="fa fa-users"></i> Customers
                </a>
                <a href="<?php echo admin_url('mailbox/folder/inbox'); ?>" class="uscap-action-button">
                    <i class="fa fa-inbox"></i> Mailbox
                </a>
            </div>
        </div>

        <div class="row uscap-metric-grid">
            <?php foreach ($heroMetrics as $metric) { ?>
                <div class="col-sm-6 col-lg-3">
                    <a href="<?php echo e($metric['href']); ?>" class="uscap-metric-card uscap-tone-<?php echo e($metric['tone']); ?>">
                        <span class="uscap-metric-shape"><i class="fa <?php echo e($metric['icon']); ?>"></i></span>
                        <span class="uscap-metric-value"><?php echo e(is_numeric($metric['value']) ? number_format($metric['value']) : $metric['value']); ?></span>
                        <span class="uscap-metric-label"><?php echo e($metric['label']); ?></span>
                        <span class="uscap-metric-caption"><?php echo e($metric['caption']); ?></span>
                    </a>
                </div>
            <?php } ?>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="uscap-panel uscap-panel-dark">
                    <div class="uscap-panel-header">
                        <div>
                            <span class="uscap-panel-kicker">At a glance</span>
                            <h2>CRM Workload</h2>
                        </div>
                        <a href="<?php echo admin_url('dashboard'); ?>">Open full widget dashboard</a>
                    </div>
                    <div class="uscap-donut-grid">
                        <?php foreach ($workload as $item) {
                            $percent = uscap_dashboard_percent($item['value'], $item['total']);
                        ?>
                            <a href="<?php echo e($item['href']); ?>" class="uscap-donut-card uscap-donut-<?php echo e($item['tone']); ?>">
                                <span class="uscap-donut" style="--value: <?php echo e($percent); ?>;">
                                    <span><?php echo e($percent); ?>%</span>
                                </span>
                                <strong><?php echo e(number_format($item['value'])); ?></strong>
                                <em><?php echo e($item['label']); ?></em>
                            </a>
                        <?php } ?>
                    </div>
                </div>

                <div class="uscap-panel">
                    <div class="uscap-panel-header">
                        <div>
                            <span class="uscap-panel-kicker">Client operations</span>
                            <h2>Quick Action Tiles</h2>
                        </div>
                    </div>
                    <div class="uscap-action-grid">
                        <?php foreach ($quickActions as $action) { ?>
                            <a href="<?php echo e($action['href']); ?>" class="uscap-action-tile">
                                <span><i class="fa <?php echo e($action['icon']); ?>"></i></span>
                                <strong><?php echo e($action['label']); ?></strong>
                                <em><?php echo e($action['desc']); ?></em>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="uscap-panel">
                    <div class="uscap-panel-header">
                        <div>
                            <span class="uscap-panel-kicker">Finance pulse</span>
                            <h2>Revenue & Documents</h2>
                        </div>
                    </div>
                    <div class="uscap-list-grid">
                        <?php foreach ($finance as $item) { ?>
                            <a href="<?php echo e($item['href']); ?>" class="uscap-list-card">
                                <span><i class="fa <?php echo e($item['icon']); ?>"></i></span>
                                <div>
                                    <strong><?php echo e(is_numeric($item['value']) ? number_format($item['value']) : $item['value']); ?></strong>
                                    <em><?php echo e($item['label']); ?></em>
                                </div>
                            </a>
                        <?php } ?>
                    </div>
                </div>

                <div class="uscap-panel">
                    <div class="uscap-panel-header">
                        <div>
                            <span class="uscap-panel-kicker">Communication & staff</span>
                            <h2>Operating Links</h2>
                        </div>
                    </div>
                    <div class="uscap-list-grid uscap-list-grid-compact">
                        <?php foreach ($operations as $item) { ?>
                            <a href="<?php echo e($item['href']); ?>" class="uscap-list-card">
                                <span><i class="fa <?php echo e($item['icon']); ?>"></i></span>
                                <div>
                                    <strong><?php echo e(is_numeric($item['value']) ? number_format($item['value']) : $item['value']); ?></strong>
                                    <em><?php echo e($item['label']); ?></em>
                                </div>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="uscap-status-strip">
            <a href="<?php echo admin_url('settings?group=email'); ?>"><i class="fa fa-envelope-open-text"></i> Email setup</a>
            <a href="<?php echo admin_url('knowledge_base'); ?>"><i class="fa fa-book"></i> Knowledge base</a>
            <a href="<?php echo admin_url('departments'); ?>"><i class="fa fa-sitemap"></i> Departments</a>
            <a href="<?php echo admin_url('utilities/calendar'); ?>"><i class="fa fa-calendar-days"></i> Calendar</a>
            <a href="<?php echo admin_url('reports'); ?>"><i class="fa fa-chart-line"></i> Reports</a>
        </div>
    </div>
</div>

<style>
.uscap-crm-dashboard {
    padding-top: 22px;
    color: #102033;
}
.uscap-dashboard-hero {
    position: relative;
    overflow: hidden;
    border-radius: 14px;
    padding: 30px;
    margin-bottom: 22px;
    background:
        radial-gradient(circle at 84% 12%, rgba(64, 184, 255, .34), transparent 28%),
        radial-gradient(circle at 18% 92%, rgba(38, 191, 146, .24), transparent 30%),
        linear-gradient(135deg, #071b3f 0%, #0e3471 48%, #123b53 100%);
    box-shadow: 0 18px 45px rgba(9, 34, 73, .22);
    color: #fff;
    display: flex;
    justify-content: space-between;
    gap: 24px;
    align-items: flex-end;
}
.uscap-dashboard-hero:before {
    content: "";
    position: absolute;
    width: 220px;
    height: 220px;
    right: 28%;
    top: -120px;
    border: 28px solid rgba(255,255,255,.08);
    border-radius: 50%;
}
.uscap-hero-copy,
.uscap-hero-actions {
    position: relative;
    z-index: 1;
}
.uscap-eyebrow,
.uscap-panel-kicker {
    display: block;
    font-size: 11px;
    letter-spacing: .16em;
    text-transform: uppercase;
    font-weight: 700;
}
.uscap-eyebrow {
    color: #8ee7ff;
    margin-bottom: 10px;
}
.uscap-hero-copy h1 {
    font-size: 34px;
    line-height: 1.1;
    font-weight: 800;
    margin: 0 0 10px;
}
.uscap-hero-copy p {
    max-width: 760px;
    color: rgba(255,255,255,.78);
    margin: 0;
    font-size: 14px;
}
.uscap-hero-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.uscap-action-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 42px;
    padding: 10px 14px;
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 10px;
    background: rgba(255,255,255,.1);
    color: #fff;
    font-weight: 700;
    text-decoration: none;
}
.uscap-action-button:hover,
.uscap-action-button:focus {
    color: #fff;
    background: rgba(255,255,255,.17);
    text-decoration: none;
}
.uscap-action-primary {
    background: #35d08f;
    border-color: #35d08f;
    color: #06251c;
}
.uscap-action-primary:hover,
.uscap-action-primary:focus {
    color: #06251c;
    background: #55dda2;
}
.uscap-metric-grid {
    margin-bottom: 10px;
}
.uscap-metric-card,
.uscap-panel,
.uscap-status-strip {
    background: #fff;
    border: 1px solid rgba(145, 160, 181, .24);
    box-shadow: 0 12px 28px rgba(16, 38, 64, .08);
}
.uscap-metric-card {
    position: relative;
    overflow: hidden;
    display: block;
    min-height: 158px;
    padding: 20px;
    margin-bottom: 22px;
    border-radius: 14px;
    color: #102033;
    text-decoration: none;
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
}
.uscap-metric-card:after {
    content: "";
    position: absolute;
    width: 120px;
    height: 120px;
    right: -38px;
    bottom: -46px;
    border-radius: 50%;
    opacity: .16;
    background: currentColor;
}
.uscap-metric-card:hover,
.uscap-metric-card:focus,
.uscap-action-tile:hover,
.uscap-list-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 18px 38px rgba(16, 38, 64, .14);
    text-decoration: none;
}
.uscap-metric-shape {
    display: inline-flex;
    width: 44px;
    height: 44px;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    margin-bottom: 16px;
    color: #fff;
}
.uscap-tone-blue { color: #1f6feb; }
.uscap-tone-blue .uscap-metric-shape { background: linear-gradient(135deg, #1f6feb, #22b8ff); }
.uscap-tone-green { color: #119765; }
.uscap-tone-green .uscap-metric-shape { background: linear-gradient(135deg, #10a26b, #36d399); }
.uscap-tone-purple { color: #7c3aed; }
.uscap-tone-purple .uscap-metric-shape { background: linear-gradient(135deg, #6d28d9, #b45cff); }
.uscap-tone-orange { color: #e05a17; }
.uscap-tone-orange .uscap-metric-shape { background: linear-gradient(135deg, #ea580c, #f6a21a); }
.uscap-metric-value {
    display: block;
    font-size: 34px;
    line-height: 1;
    color: #0f172a;
    font-weight: 800;
}
.uscap-metric-label {
    display: block;
    margin-top: 8px;
    color: #17263a;
    font-weight: 800;
}
.uscap-metric-caption {
    display: block;
    margin-top: 3px;
    color: #65758b;
    font-size: 12px;
}
.uscap-panel {
    border-radius: 14px;
    padding: 22px;
    margin-bottom: 22px;
}
.uscap-panel-dark {
    background: linear-gradient(135deg, #10203f, #142f5c);
    color: #fff;
}
.uscap-panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 18px;
}
.uscap-panel h2 {
    margin: 2px 0 0;
    font-size: 19px;
    font-weight: 800;
}
.uscap-panel-kicker {
    color: #65809d;
}
.uscap-panel-dark .uscap-panel-kicker {
    color: #8ee7ff;
}
.uscap-panel-header a {
    color: inherit;
    opacity: .78;
    font-size: 12px;
    font-weight: 700;
}
.uscap-donut-grid,
.uscap-action-grid,
.uscap-list-grid {
    display: grid;
    gap: 14px;
}
.uscap-donut-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}
.uscap-donut-card {
    display: block;
    padding: 16px 12px;
    border-radius: 14px;
    background: rgba(255,255,255,.08);
    color: #fff;
    text-align: center;
    text-decoration: none;
    border: 1px solid rgba(255,255,255,.12);
}
.uscap-donut-card:hover,
.uscap-donut-card:focus {
    color: #fff;
    background: rgba(255,255,255,.13);
    text-decoration: none;
}
.uscap-donut {
    --accent: #3fb8ff;
    display: inline-flex;
    width: 102px;
    height: 102px;
    border-radius: 50%;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    background: conic-gradient(var(--accent) calc(var(--value) * 1%), rgba(255,255,255,.18) 0);
}
.uscap-donut:before {
    content: "";
    position: absolute;
}
.uscap-donut span {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #142442;
    font-weight: 800;
}
.uscap-donut-orange .uscap-donut { --accent: #f59e0b; }
.uscap-donut-green .uscap-donut { --accent: #22c55e; }
.uscap-donut-purple .uscap-donut { --accent: #a855f7; }
.uscap-donut-card strong,
.uscap-donut-card em {
    display: block;
    font-style: normal;
}
.uscap-donut-card strong {
    font-size: 22px;
    line-height: 1;
}
.uscap-donut-card em {
    margin-top: 5px;
    color: rgba(255,255,255,.74);
    font-size: 12px;
}
.uscap-action-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}
.uscap-action-tile,
.uscap-list-card {
    border-radius: 12px;
    border: 1px solid #e4ebf3;
    background: linear-gradient(180deg, #fff, #f8fbff);
    color: #12223a;
    text-decoration: none;
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
}
.uscap-action-tile {
    min-height: 132px;
    padding: 16px;
}
.uscap-action-tile span,
.uscap-list-card span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    color: #fff;
    background: linear-gradient(135deg, #2563eb, #14b8a6);
}
.uscap-action-tile span {
    width: 42px;
    height: 42px;
    margin-bottom: 14px;
}
.uscap-action-tile strong,
.uscap-action-tile em {
    display: block;
    font-style: normal;
}
.uscap-action-tile strong {
    font-weight: 800;
}
.uscap-action-tile em {
    margin-top: 5px;
    color: #66758a;
    font-size: 12px;
    line-height: 1.35;
}
.uscap-list-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.uscap-list-card {
    min-height: 86px;
    padding: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.uscap-list-card span {
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
}
.uscap-list-card strong,
.uscap-list-card em {
    display: block;
    font-style: normal;
}
.uscap-list-card strong {
    font-size: 20px;
    line-height: 1;
    color: #102033;
    font-weight: 800;
}
.uscap-list-card em {
    margin-top: 5px;
    color: #66758a;
    font-size: 12px;
}
.uscap-status-strip {
    border-radius: 14px;
    padding: 12px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 30px;
}
.uscap-status-strip a {
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 10px;
    padding: 9px 12px;
    background: #eef5ff;
    color: #173b70;
    font-weight: 700;
    text-decoration: none;
}
.uscap-status-strip a:hover,
.uscap-status-strip a:focus {
    background: #ddeaff;
    text-decoration: none;
}
@media (max-width: 1199px) {
    .uscap-donut-grid,
    .uscap-action-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 767px) {
    .uscap-dashboard-hero {
        padding: 22px;
        display: block;
    }
    .uscap-hero-actions {
        justify-content: flex-start;
        margin-top: 18px;
    }
    .uscap-hero-copy h1 {
        font-size: 26px;
    }
    .uscap-donut-grid,
    .uscap-action-grid,
    .uscap-list-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php init_tail(); ?>
</body>
</html>
