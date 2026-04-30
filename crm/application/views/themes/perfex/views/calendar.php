<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
    .client-calendar-shell {
        margin-bottom: 28px;
    }

    .client-calendar-hero {
        background:
            linear-gradient(135deg, rgba(15, 39, 66, 0.96) 0%, rgba(36, 89, 155, 0.92) 100%),
            radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 42%);
        border-radius: 30px;
        box-shadow: 0 28px 70px rgba(15, 39, 66, 0.18);
        color: #fff;
        margin-bottom: 24px;
        overflow: hidden;
        padding: 34px 38px;
        position: relative;
    }

    .client-calendar-hero::before {
        background: radial-gradient(circle at bottom left, rgba(200,162,77,0.14), transparent 30%);
        content: '';
        inset: 0;
        position: absolute;
    }

    .client-calendar-hero > * {
        position: relative;
        z-index: 1;
    }

    .client-calendar-eyebrow {
        color: rgba(255,255,255,0.78);
        display: inline-block;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .26em;
        margin-bottom: 12px;
        text-transform: uppercase;
    }

    .client-calendar-grid {
        display: grid;
        gap: 28px;
        grid-template-columns: 1.35fr .85fr;
    }

    .client-calendar-title {
        font-size: 44px;
        font-weight: 800;
        letter-spacing: -.04em;
        line-height: 1.02;
        margin: 0 0 12px;
        max-width: 760px;
    }

    .client-calendar-copy {
        color: rgba(255,255,255,0.86);
        font-size: 17px;
        line-height: 1.7;
        margin: 0 0 20px;
        max-width: 760px;
    }

    .client-calendar-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .client-calendar-actions .btn {
        border-radius: 999px;
        font-weight: 700;
        min-width: 180px;
        padding: 12px 20px;
    }

    .client-calendar-actions .btn-primary {
        box-shadow: 0 12px 28px rgba(37, 99, 235, 0.35);
    }

    .client-calendar-card {
        background: rgba(255,255,255,0.14);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 24px;
        padding: 22px 22px 20px;
    }

    .client-calendar-card h4 {
        color: #fff;
        font-size: 20px;
        font-weight: 800;
        margin: 0 0 14px;
    }

    .client-calendar-step-list {
        display: grid;
        gap: 12px;
    }

    .client-calendar-step {
        align-items: flex-start;
        background: rgba(255,255,255,0.1);
        border-radius: 18px;
        display: flex;
        gap: 14px;
        padding: 14px 16px;
    }

    .client-calendar-step strong {
        align-items: center;
        background: rgba(255,255,255,0.16);
        border-radius: 999px;
        color: #fff;
        display: inline-flex;
        font-size: 12px;
        height: 30px;
        justify-content: center;
        min-width: 30px;
    }

    .client-calendar-step p {
        color: rgba(255,255,255,0.88);
        margin: 2px 0 0;
    }

    .client-calendar-panel {
        background: #fff;
        border: 1px solid rgba(15,39,66,.08);
        border-radius: 28px;
        box-shadow: 0 18px 48px rgba(15,39,66,.08);
        overflow: hidden;
    }

    .client-calendar-panel-head {
        border-bottom: 1px solid rgba(15,39,66,.08);
        padding: 22px 26px 18px;
    }

    .client-calendar-panel-head h3 {
        color: #102a43;
        font-size: 28px;
        font-weight: 800;
        margin: 0 0 8px;
    }

    .client-calendar-panel-head p {
        color: #486581;
        margin: 0;
        max-width: 760px;
    }

    .client-calendar-panel-body {
        padding: 24px;
    }

    .client-calendar-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
    }

    .client-calendar-legend span {
        align-items: center;
        background: #f5f9ff;
        border: 1px solid rgba(37,99,235,.1);
        border-radius: 999px;
        color: #1f3c88;
        display: inline-flex;
        font-size: 13px;
        font-weight: 600;
        gap: 8px;
        padding: 8px 14px;
    }

    .client-calendar-legend i {
        border-radius: 999px;
        display: inline-block;
        height: 10px;
        width: 10px;
    }

    @media (max-width: 991px) {
        .client-calendar-grid {
            grid-template-columns: 1fr;
        }

        .client-calendar-title {
            font-size: 34px;
        }
    }
</style>

<div class="client-calendar-shell">
    <div class="client-calendar-hero">
        <span class="client-calendar-eyebrow">Client Scheduling Center</span>
        <div class="client-calendar-grid">
            <div>
                <h1 class="client-calendar-title">Track every meeting request, approval, and confirmed session in one clean calendar.</h1>
                <p class="client-calendar-copy">This calendar shows your portal events together with your appointment workflow. When you request a meeting, it appears here immediately, routes to the selected staff member, and updates automatically when the bank approves or reschedules it.</p>
                <div class="client-calendar-actions">
                    <a href="<?php echo e($book_appointment_url); ?>" class="btn btn-primary">Book an Appointment</a>
                    <a href="<?php echo site_url('clients/projects'); ?>" class="btn btn-default">Open Projects</a>
                </div>
            </div>
            <div class="client-calendar-card">
                <h4>How This Works</h4>
                <div class="client-calendar-step-list">
                    <div class="client-calendar-step">
                        <strong>1</strong>
                        <p>Select the staff member, meeting date, and time from the booking wizard.</p>
                    </div>
                    <div class="client-calendar-step">
                        <strong>2</strong>
                        <p>The appointment appears on your calendar immediately and notifies the selected staff member for approval.</p>
                    </div>
                    <div class="client-calendar-step">
                        <strong>3</strong>
                        <p>Once accepted or rescheduled, the calendar updates on both sides and the confirmation details are sent out.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="client-calendar-panel">
        <div class="client-calendar-panel-head">
            <h3>My Calendar</h3>
            <p>Your appointment requests, approved meetings, and existing portal events stay centralized here so you can plan, follow up, and keep each bank interaction on schedule.</p>
        </div>
        <div class="client-calendar-panel-body">
            <div class="client-calendar-legend">
                <span><i style="background:#2563eb;"></i>Appointments and confirmed sessions</span>
                <span><i style="background:#eb25ba;"></i>Waiting for staff approval</span>
                <span><i style="background:#2ceeab;"></i>Approved or confirmed</span>
            </div>
            <div id="calendar"></div>
        </div>
    </div>
</div>
