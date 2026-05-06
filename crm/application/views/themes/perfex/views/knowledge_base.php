<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$totalArticles = 0;
foreach ($articles as $categorySummary) {
    $totalArticles += count($categorySummary['articles']);
}
?>
<style>
    .uscap-kb-shell {
        background: linear-gradient(180deg, #f8fafc 0%, #edf3f9 100%);
        border: 1px solid #dbe5f1;
        border-radius: 18px;
        box-shadow: 0 22px 60px rgba(16, 38, 64, .12);
        margin-bottom: 38px;
        overflow: hidden;
    }

    .uscap-kb-summary {
        background: #fff;
        border-bottom: 1px solid #dbe5f1;
        display: grid;
        gap: 1px;
        grid-template-columns: repeat(3, 1fr);
    }

    .uscap-kb-summary > div {
        background: #fbfdff;
        padding: 20px 24px;
    }

    .uscap-kb-summary span {
        color: #64748b;
        display: block;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .uscap-kb-summary strong {
        color: #102033;
        display: block;
        font-size: 24px;
        margin-top: 5px;
    }

    .uscap-kb-body {
        padding: 28px;
    }

    .uscap-kb-section-heading {
        align-items: center;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin-bottom: 18px;
    }

    .uscap-kb-section-heading h3 {
        color: #102033;
        font-size: 22px;
        font-weight: 900;
        margin: 0;
    }

    .uscap-kb-section-heading p {
        color: #64748b;
        margin: 4px 0 0;
    }

    .uscap-kb-pill {
        background: #eef6ff;
        border: 1px solid #dbeafe;
        border-radius: 999px;
        color: #155b99;
        display: inline-block;
        font-size: 12px;
        font-weight: 800;
        padding: 8px 12px;
    }

    .uscap-kb-empty {
        background: #fff;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        color: #64748b;
        padding: 24px;
    }

    @media (max-width: 767px) {
        .uscap-kb-summary {
            grid-template-columns: 1fr;
        }

        .uscap-kb-body {
            padding: 18px;
        }

        .uscap-kb-section-heading {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
<div class="uscap-kb-shell">
    <div class="uscap-kb-summary">
        <div>
            <span>Knowledge Groups</span>
            <strong><?= e(count($articles)); ?></strong>
        </div>
        <div>
            <span>Published Articles</span>
            <strong><?= e($totalArticles); ?></strong>
        </div>
        <div>
            <span>Support Path</span>
            <strong>CRM Help Center</strong>
        </div>
    </div>
	<div class="uscap-kb-body">
        <div class="uscap-kb-section-heading">
            <div>
                <h3><?= isset($search_results) ? e($title) : (isset($category) ? e($title) : 'Knowledge Base'); ?></h3>
                <p>Browse approved guidance and support articles from the CRM knowledge library.</p>
            </div>
            <span class="uscap-kb-pill">USCPB Support</span>
        </div>
		<?php if (count($articles) == 0) { ?>
		<p class="no-margin uscap-kb-empty">
			<?= _l('clients_knowledge_base_articles_not_found'); ?>
		</p>
		<?php } ?>
		<?php if (isset($category)) {
		    // Category articles list
		    get_template_part('knowledge_base/category_articles_list', ['articles' => $articles]);
		} elseif (isset($search_results)) {
		    // Search results
		    get_template_part('knowledge_base/search_results', ['articles' => $articles]);
		} else {
		    // Default page
		    get_template_part('knowledge_base/categories', ['articles' => $articles]);
		}
hooks()->do_action('after_kb_groups_customers_area');
?>
	</div>
</div>
