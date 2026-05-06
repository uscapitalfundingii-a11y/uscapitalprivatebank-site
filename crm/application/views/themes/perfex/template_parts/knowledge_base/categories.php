<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
    .uscap-kb-category-grid {
        display: grid;
        gap: 18px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .uscap-kb-category-card {
        background: #fff;
        border: 1px solid #dbe5f1;
        border-radius: 14px;
        box-shadow: 0 14px 34px rgba(16, 38, 64, .08);
        min-height: 190px;
        padding: 22px;
        position: relative;
        transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
    }

    .uscap-kb-category-card:hover {
        border-color: #b8cae2;
        box-shadow: 0 18px 42px rgba(16, 38, 64, .12);
        transform: translateY(-2px);
    }

    .uscap-kb-category-card:before {
        background: linear-gradient(135deg, #1f6feb, #14b8d4);
        border-radius: 999px;
        content: "";
        height: 10px;
        left: 22px;
        position: absolute;
        top: 20px;
        width: 44px;
    }

    .uscap-kb-category-card h3 {
        font-size: 18px;
        font-weight: 900;
        line-height: 1.3;
        margin: 24px 0 10px;
    }

    .uscap-kb-category-card h3 a {
        color: #102033;
        text-decoration: none;
    }

    .uscap-kb-category-card h3 a:hover {
        color: #1f6feb;
    }

    .uscap-kb-category-card p {
        color: #64748b;
        font-size: 13px;
        line-height: 1.65;
    }

    .uscap-kb-count {
        background: #fff8e8;
        border: 1px solid #f3dfaa;
        border-radius: 999px;
        color: #835005;
        display: inline-block;
        font-size: 11px;
        font-weight: 900;
        padding: 7px 10px;
    }

    @media (max-width: 991px) {
        .uscap-kb-category-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .uscap-kb-category-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
<ul role="list" class="uscap-kb-category-grid">
    <?php foreach ($articles as $category) { ?>
    <li class="uscap-kb-category-card">
        <div>
            <h3>
                <a href="<?= site_url('knowledge-base/category/' . e($category['group_slug'])); ?>"
                    >
                    <?= e($category['name']); ?>
                </a>
            </h3>
            <span class="uscap-kb-count">
                <?= e(count($category['articles'])); ?> articles
            </span>
        </div>
        <p>
            <?= e($category['description']); ?>
        </p>
    </li>
    <?php } ?>
</ul>
