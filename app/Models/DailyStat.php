<?php

namespace App\Models;

use Database\Factories\DailyStatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyStat extends Model
{
    /** @use HasFactory<DailyStatFactory> */
    use HasFactory;

    protected $table = 'cms_daily_stats';

    // Primary key berupa tanggal, tanpa auto-increment & tanpa timestamps.
    protected $primaryKey = 'date';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['date', 'visits', 'unique_visitors', 'pageviews'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'visits' => 'integer',
            'unique_visitors' => 'integer',
            'pageviews' => 'integer',
        ];
    }
}
