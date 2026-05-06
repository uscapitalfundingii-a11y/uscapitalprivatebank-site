<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
    .uscap-kb-hero {
        background:
            linear-gradient(135deg, rgba(15, 31, 53, .96), rgba(24, 44, 84, .94)),
            radial-gradient(circle at 88% 0%, rgba(20, 184, 212, .18), transparent 30%);
        border: 1px solid rgba(200, 162, 77, .28);
        border-radius: 18px;
        box-shadow: 0 24px 60px rgba(15, 31, 53, .22);
        color: #fff;
        margin: 18px auto 28px;
        max-width: 1140px;
        overflow: hidden;
        padding: 0;
        position: relative;
    }

    .uscap-kb-hero:after {
        background: linear-gradient(90deg, #1f6feb, #14b8d4, #c8a24d);
        bottom: 0;
        content: "";
        height: 5px;
        left: 0;
        position: absolute;
        right: 0;
    }

    .uscap-kb-hero .kb-search {
        padding: 42px 22px 46px;
    }

    .uscap-kb-eyebrow {
        color: #f2d17d;
        display: block;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .16em;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .uscap-kb-hero .kb-search-heading {
        color: #fff;
        font-size: 34px;
        line-height: 1.15;
        margin-bottom: 12px;
    }

    .uscap-kb-subtitle {
        color: rgba(255, 255, 255, .76);
        font-size: 14px;
        line-height: 1.7;
        margin: 0 auto 24px;
        max-width: 720px;
    }

    .uscap-kb-hero .input-group {
        background: rgba(255, 255, 255, .12);
        border: 1px solid rgba(255, 255, 255, .22);
        border-radius: 999px;
        box-shadow: 0 18px 38px rgba(4, 10, 20, .18);
        padding: 7px;
    }

    .uscap-kb-hero .kb-search-input {
        border: 0;
        border-radius: 999px 0 0 999px;
        box-shadow: none;
        height: 48px;
        padding-left: 46px;
    }

    .uscap-kb-hero .kb-search-button {
        border: 0;
        border-radius: 999px !important;
        height: 48px;
        min-width: 130px;
        background: linear-gradient(135deg, #1f6feb, #14b8d4);
        font-weight: 800;
        box-shadow: 0 12px 28px rgba(31, 111, 235, .28);
    }

    .uscap-kb-hero .kb-search-icon {
        color: #64748b;
        left: 22px;
        line-height: 62px;
        pointer-events: none;
        z-index: 4;
    }

    @media (max-width: 767px) {
        .uscap-kb-hero {
            border-radius: 14px;
            margin: 14px 10px 22px;
        }

        .uscap-kb-hero .kb-search {
            padding: 30px 12px 34px;
        }

        .uscap-kb-hero .kb-search-heading {
            font-size: 27px;
        }

        .uscap-kb-hero .input-group {
            border-radius: 18px;
        }

        .uscap-kb-hero .kb-search-input,
        .uscap-kb-hero .kb-search-button {
            border-radius: 12px !important;
        }
    }
</style>
<div class="jumbotron kb-search-jumbotron uscap-kb-hero">
    <div class="kb-search">
        <div class="container">
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <div class="text-center">
                        <span class="uscap-kb-eyebrow">Client Support Library</span>
                        <h2 class="mbot30 kb-search-heading tw-font-semibold">
                            <?= _l('kb_search_articles'); ?>
                        </h2>
                        <p class="uscap-kb-subtitle">
                            Search approved help articles, onboarding guidance, banking terms, and support routing notes from the US Capital Private Bank CRM knowledge center.
                        </p>
                        <?= form_open(site_url('knowledge-base/search'), ['method' => 'GET', 'id' => 'kb-search-form']); ?>
                        <div class="form-group has-feedback has-feedback-left">
                            <div class="input-group">
                                <input type="search" name="q"
                                    placeholder="<?= _l('have_a_question'); ?>"
                                    class="form-control kb-search-input"
                                    value="<?= e($this->input->get('q', false)); ?>">
                                <span class="input-group-btn">
                                    <button type="submit"
                                        class="btn btn-primary kb-search-button"><?= _l('kb_search'); ?></button>
                                </span>
                                <i class="fa-solid fa-magnifying-glass form-control-feedback kb-search-icon"></i>
                            </div>
                        </div>
                        <?= form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
