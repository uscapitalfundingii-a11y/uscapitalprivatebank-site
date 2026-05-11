<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BankCoordinateDirectory
{
    public static function all(): Collection
    {
        $path = resource_path('data/bank_coordinates.json');

        if (!File::exists($path)) {
            return collect();
        }

        $rows = json_decode(File::get($path), true);

        if (!is_array($rows)) {
            return collect();
        }

        return collect($rows)
            ->filter(fn ($row) => is_array($row) && !empty($row['name']))
            ->values();
    }

    public static function findByName(?string $name): ?array
    {
        if (!$name) {
            return null;
        }

        $needle = Str::lower(trim($name));

        return self::all()->first(function ($row) use ($needle) {
            return Str::lower($row['name'] ?? '') === $needle;
        });
    }

    public static function search(?string $query = null, int $limit = 25): Collection
    {
        $banks = self::all();
        $query = Str::lower(trim((string) $query));

        if ($query === '') {
            return $banks->take($limit)->values();
        }

        return $banks->filter(function ($row) use ($query) {
            $haystack = Str::lower(implode(' ', [
                $row['name'] ?? '',
                $row['swift_code'] ?? '',
                $row['country'] ?? '',
                $row['city'] ?? '',
                $row['address'] ?? '',
            ]));

            return Str::contains($haystack, $query);
        })->take($limit)->values();
    }
}
