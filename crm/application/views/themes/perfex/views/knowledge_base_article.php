<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
    .uscap-kb-article-shell {
        margin-bottom: 38px;
    }

    .uscap-kb-article-panel {
        background: #fff;
        border: 1px solid #dbe5f1;
        border-radius: 18px;
        box-shadow: 0 22px 60px rgba(16, 38, 64, .12);
        overflow: hidden;
    }

    .uscap-kb-article-panel:before {
        background: linear-gradient(90deg, #1f6feb, #14b8d4, #c8a24d);
        content: "";
        display: block;
        height: 5px;
    }

    .uscap-kb-article-panel .panel-body {
        padding: 32px;
    }

    .uscap-kb-back {
        background: #eef6ff;
        border: 1px solid #dbeafe;
        border-radius: 999px;
        color: #155b99;
        display: inline-block;
        font-size: 12px;
        font-weight: 800;
        margin-bottom: 18px;
        padding: 8px 12px;
        text-decoration: none !important;
    }

    .uscap-kb-article-title {
        color: #102033;
        font-size: 30px;
        font-weight: 900;
        letter-spacing: 0;
        line-height: 1.18;
        margin: 0 0 22px;
    }

    .uscap-kb-article-content {
        color: #334155;
        font-size: 15px;
        line-height: 1.85;
    }

    .uscap-kb-useful {
        background: linear-gradient(180deg, #f8fbff, #eef5ff);
        border: 1px solid #dbe5f1;
        border-radius: 14px;
        margin-top: 28px;
        padding: 20px;
    }

    .uscap-kb-related-panel {
        background: #fff;
        border: 1px solid #dbe5f1;
        border-radius: 16px;
        box-shadow: 0 18px 42px rgba(16, 38, 64, .10);
        padding: 22px;
    }

    .uscap-kb-related-panel h4 {
        color: #102033;
        font-weight: 900;
        margin-bottom: 14px;
    }

    .uscap-kb-related-panel li {
        border-bottom: 1px solid #edf2f7;
        padding: 14px 0;
    }

    .uscap-kb-related-panel li:last-child {
        border-bottom: 0;
    }

    .uscap-kb-related-panel a {
        color: #102033;
        font-weight: 800;
        text-decoration: none;
    }

    .uscap-kb-related-panel a:hover {
        color: #1f6feb;
    }

    @media (max-width: 767px) {
        .uscap-kb-article-panel .panel-body {
            padding: 22px;
        }

        .uscap-kb-article-title {
            font-size: 25px;
        }
    }
</style>
<div class="section-knowledge-base uscap-kb-article-shell">
    <div class="row">
        <div
            class="col-md-<?= count($related_articles) == 0 ? 12 : 8; ?>">
            <div class="panel_s uscap-kb-article-panel">
                <div class="panel-body">
                    <a class="uscap-kb-back" href="<?= site_url('knowledge-base'); ?>">
                        Back to Knowledge Base
                    </a>
                    <h1
                        class="kb-article-single-heading uscap-kb-article-title">
                        <?= e($article->subject); ?>
                    </h1>
                    <div class="tc-content kb-article-content uscap-kb-article-content">
                        <?= $article->description; ?>
                    </div>
                    <div class="uscap-kb-useful">
                        <h4 class="tw-font-medium tw-text-lg tw-mt-0">
                            <?= _l('clients_knowledge_base_find_useful'); ?>
                        </h4>
                        <div class="answer_response tw-mb-2 tw-text-neutral-500"></div>
                        <div class="btn-group article_useful_buttons" role="group">
                            <button type="button" data-answer="1" class="btn btn-success">
                                <?= _l('clients_knowledge_base_find_useful_yes'); ?>
                            </button>
                            <input type="hidden" name="articleid"
                                value="<?= e($article->articleid); ?>">
                            <button type="button" data-answer="0" class="btn btn-danger">
                                <?= _l('clients_knowledge_base_find_useful_no'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php hooks()->do_action('after_single_knowledge_base_article_customers_area', $article->articleid); ?>
        </div>
        <?php if (count($related_articles) > 0) { ?>
        <div class="col-md-4">
            <div class="uscap-kb-related-panel">
                <h4 class="kb-related-heading tw-font-semibold tw-text-lg tw-text-neutral-700 tw-mt-0 tw-my-0">
                    <?= _l('related_knowledgebase_articles'); ?>
                </h4>
                <ul class="articles_list list-unstyled">
                    <?php foreach ($related_articles as $relatedArticle) { ?>
                    <li>
                        <h4 class="article-heading article-related-heading tw-text-normal tw-font-medium tw-my-0">
                            <a href="<?= site_url('knowledge-base/article/' . $relatedArticle['slug']); ?>">
                                <?= e($relatedArticle['subject']); ?>
                            </a>
                        </h4>
                        <div class="tw-text-neutral-500">
                            <?= mb_substr(strip_tags($relatedArticle['description']), 0, 100); ?>...
                        </div>
                    </li>
                    <?php } ?>
                </ul>
            </div>
        </div>
        <?php }	?>
    </div>
</div>
