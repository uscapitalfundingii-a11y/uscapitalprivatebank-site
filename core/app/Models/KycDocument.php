<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class KycDocument extends Model
{
    protected $fillable = [
        'field_label',
        'field_name',
        'title',
        'slug',
        'stored_name',
        'original_name',
        'extension',
        'mime_type',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public const CACHE_KEY = 'kyc_document_library';

    protected static function booted(): void
    {
        static::saved(fn () => static::clearLibraryCache());
        static::deleted(fn () => static::clearLibraryCache());
    }

    public static function clearLibraryCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }

    public static function library(): array
    {
        return Cache::rememberForever(static::CACHE_KEY, function () {
            return static::query()
                ->where('status', true)
                ->orderBy('title')
                ->get()
                ->mapWithKeys(function (self $document) {
                    return [$document->field_label => $document];
                })
                ->all();
        });
    }

    public static function resolveForField(object|array $field): ?self
    {
        $fieldLabel = is_array($field) ? Arr::get($field, 'label') : ($field->label ?? null);

        if (!$fieldLabel) {
            return null;
        }

        return static::library()[$fieldLabel] ?? null;
    }

    public static function stableTitle(string $fieldName): string
    {
        return trim((string) preg_replace('/\s+\d{4}\b/', '', $fieldName));
    }

    public static function stableSlug(string $fieldName): string
    {
        return Str::slug(static::stableTitle($fieldName));
    }

    public function getRelativePathAttribute(): string
    {
        return trim(getFilePath('verify') . '/kyc_documents/' . $this->stored_name, '/');
    }

    public function getAbsolutePathAttribute(): string
    {
        return base_path($this->relative_path);
    }

    public function getDownloadNameAttribute(): string
    {
        return $this->title . '.' . $this->extension;
    }
}
