<?php

namespace App\Models;

use App\Enums\MessageType;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    protected $table = 'cms_contact_messages';

    protected $fillable = ['name', 'phone', 'email', 'message', 'type', 'status'];

    protected function casts(): array
    {
        return [
            'type' => MessageType::class,
            'status' => 'string',
        ];
    }

    // Pesan yang belum ditindaklanjuti.
    public function scopeBaru(Builder $q): Builder
    {
        return $q->where('status', 'baru');
    }
}
