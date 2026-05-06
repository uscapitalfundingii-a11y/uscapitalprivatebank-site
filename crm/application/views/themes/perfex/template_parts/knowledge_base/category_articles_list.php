<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-12">
    <style>
        .uscap-kb-article-list {
            display: grid;
            gap: 14px;
        }

        .uscap-kb-article-group {
            display: grid;
            gap: 14px;
        }

        .uscap-kb-article-item {
            background: #fff;
            border: 1px solid #dbe5f1;
            border-radius: 14px;
            box-shadow: 0 12px 28px rgba(16, 38, 64, .07);
            padding: 20px;
            transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
        }

        .uscap-kb-article-item:hover {
            border-color: #b8cae2;
            box-shadow: 0 16px 38px rgba(16, 38, 64, .11);
            transform: translateY(-1px);
        }

        .uscap-kb-article-title {
            font-size: 17px;
            font-weight: 900;
            margin: 0 0 8px;
        }

        .uscap-kb-article-title a {
            color: #102033;
            text-decoration: none;
        }

        .uscap-kb-article-title a:hover {
            color: #1f6feb;
        }

        .uscap-kb-date {
            background: #eef6ff;
            border-radius: 999px;
            color: #155b99;
            font-size: 11px;
            font-weight: 800;
            padding: 6px 9px;
            white-space: nowrap;
        }

        .uscap-kb-excerpt {
            color: #64748b;
            font-size: 13px;
            line-height: 1.7;
            margin-top: 8px;
        }
    </style>
    <div class="uscap-kb-article-list">
        <?php foreach ($articles as $category) { ?>
        <div class="uscap-kb-article-group">
            <ul class="list-unstyled articles_list uscap-kb-article-group">
                <?php foreach ($category['articles'] as $article) { ?>
                <li class="uscap-kb-article-item">
                    <div class="sm:tw-flex sm:tw-justify-between">
                        <h4 class="uscap-kb-article-title">
                            <a href="<?= site_url('knowledge-base/article/' . $article['slug']); ?>"
                                >
                                <?= e($article['subject']); ?>
                            </a>
                        </h4>
                        <span class="uscap-kb-date">
                            <?= e(_dt($article['datecreated'])); ?>
                        </span>
                    </div>
                    <div class="uscap-kb-excerpt">
                        <?= process_text_content_for_display(strip_tags(mb_substr($article['description'], 0, 250))); ?>...
                    </div>
                </li>
                <?php } ?>
            </ul>
        </div>
        <?php } ?>
    </div>
</div>
