<?php

namespace App\Models;

use Database\Factories\CommentReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentReport extends Model
{
    /** @use HasFactory<CommentReportFactory> */
    use HasFactory;

    protected $table = 'cms_comment_reports';

    protected $fillable = ['comment_id', 'reason', 'visitor_hash'];

    // ---------- relationships ----------

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }
}
