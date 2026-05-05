<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s">
    <div class="panel-body">
        <p>
            <a href="<?= site_url('regulatorynotice'); ?>" class="btn btn-default">
                Back to Regulatory Notice
            </a>
        </p>
        <h4 class="terms-and-conditions-heading">
            <?= _l('terms_and_conditions'); ?>
        </h4>
        <hr />
        <div class="tc-content terms-and-conditions-content">
            <?= $terms; ?>
        </div>
        <hr />
        <p class="text-muted">
            These terms are part of the public legal and regulatory disclosure set.
            <a href="<?= site_url('regulatorynotice'); ?>">Return to the Regulatory Notice hub.</a>
        </p>
    </div>
</div>
