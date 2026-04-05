<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model {
    public static function toTranslationLocale(?string $code): string
    {
        $normalized = strtolower(trim((string) $code));

        if ($normalized === '' || $normalized === 'en') {
            return 'en';
        }

        return match ($normalized) {
            'cn', 'zh', 'zh-cn', 'zh-hans' => 'zh-CN',
            'zh-tw', 'zh-hk', 'zh-hant' => 'zh-TW',
            'he', 'he-il' => 'iw',
            'jp', 'ja', 'ja-jp' => 'ja',
            'id', 'id-id' => 'id',
            'af', 'af-za' => 'af',
            'ar', 'ar-ae', 'ar-are', 'ar-qa' => 'ar',
            'fa', 'fa-ir' => 'fa',
            'ko', 'ko-kr' => 'ko',
            'ro', 'ro-ro' => 'ro',
            'ru', 'ru-ru' => 'ru',
            'tr', 'tr-cy' => 'tr',
            'ur', 'ur-in' => 'ur',
            'sw', 'sw-ke' => 'sw',
            'ga', 'ga-ie' => 'ga',
            'scn', 'scn-it' => 'it',
            default => explode('-', $normalized)[0],
        };
    }
}
