<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s">
    <div class="panel-body">
        <p>
            <a href="<?= site_url('regulatorynotice'); ?>" class="btn btn-default">
                Back to Regulatory Notice
            </a>
        </p>
        <h4 class="privacy-policy-heading">
            <?= _l('privacy_policy'); ?>
        </h4>
        <hr />
        <div class="tc-content privacy-policy">
            <?= $policy; ?>
        </div>
        <hr />
        <p class="text-muted">
            This policy is part of the public legal and regulatory disclosure set.
            <a href="<?= site_url('regulatorynotice'); ?>">Return to the Regulatory Notice hub.</a>
        </p>
    </div>
</div>
