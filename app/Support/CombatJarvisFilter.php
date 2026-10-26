<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class CombatJarvisFilter
{
    public static function selection(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = strtoupper(trim($value));

        return in_array($value, ['YES', 'NO', '-'], true) ? $value : null;
    }

    public static function apply(Builder $query, mixed $value): void
    {
        $selection = self::selection($value);
        if ($selection === null) {
            return;
        }

        // Match CombatSourceDetails::flattened(): flat/manual values override
        // DATABASE, which in turn overrides DATABASE_REVENUE.
        $field = "UPPER(TRIM(COALESCE(CASE
            WHEN JSON_CONTAINS_PATH(combat_sites.source_details, 'one', '$.available_on_jarvis')
                THEN JSON_UNQUOTE(JSON_EXTRACT(combat_sites.source_details, '$.available_on_jarvis'))
            WHEN JSON_CONTAINS_PATH(combat_sites.source_details, 'one', '$.database.available_on_jarvis')
                THEN JSON_UNQUOTE(JSON_EXTRACT(combat_sites.source_details, '$.database.available_on_jarvis'))
            ELSE JSON_UNQUOTE(JSON_EXTRACT(combat_sites.source_details, '$.database_revenue.available_on_jarvis'))
        END, '')))";

        if ($selection === '-') {
            $query->whereRaw("{$field} IN ('', '-', 'NULL')");
        } else {
            $query->whereRaw("{$field} = ?", [$selection]);
        }
    }
}
