<?php

namespace Modules\Search\Models;

use Modules\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    use HasUuid;

    protected $fillable = [
        'query',
        'normalized_query',
        'user_id',
        'session_id',
        'ip_address',
        'filters',
        'results_count',
        'search_duration_ms',
        'source',
        'has_results',
    ];

    protected $casts = [
        'filters'          => 'array',
        'results_count'    => 'integer',
        'search_duration_ms' => 'float',
        'has_results'      => 'boolean',
    ];

    public function scopeByQuery($query, string $q)
    {
        return $query->where('normalized_query', static::normalize($q));
    }

    public function scopePopular($query, int $limit = 10)
    {
        return $query->selectRaw('normalized_query as query, COUNT(*) as count, AVG(results_count) as avg_results')
            ->whereNotNull('normalized_query')
            ->groupBy('normalized_query')
            ->orderByDesc('count')
            ->limit($limit);
    }

    public function scopeWithoutResults($query)
    {
        return $query->where('has_results', false);
    }

    public static function normalize(string $query): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $query)));
    }
}
