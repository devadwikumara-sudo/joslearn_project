<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class StudentSearch
{
    public static function apply(Builder $query, string $search): Builder
    {
        $search = trim(preg_replace('/\s+/u', ' ', $search) ?? '');
        if ($search === '') {
            return $query;
        }

        $fields = [
            'nama_lengkap',
            'nama_panggilan',
            'nisn',
            'nomor_induk',
            'nik',
            'tempat_lahir',
            'nama_ayah',
            'nama_ibu',
            'nama_wali',
        ];
        $availableFields = array_values(array_filter(
            $fields,
            static fn (string $field): bool => Schema::hasColumn('siswa_biodata', $field)
        ));

        $tokens = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($tokens as $token) {
            $term = trim($token);
            $query->where(function (Builder $tokenQuery) use ($availableFields, $term, $tokens): void {
                foreach ($availableFields as $index => $field) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $isNameField = in_array($field, ['nama_lengkap', 'nama_panggilan', 'nama_ayah', 'nama_ibu', 'nama_wali'], true);
                    $pattern = $isNameField && count($tokens) === 1
                        ? "{$term}%"
                        : "%{$term}%";
                    $tokenQuery->{$method}($field, 'like', $pattern);
                }
            });
        }

        return $query;
    }
}
