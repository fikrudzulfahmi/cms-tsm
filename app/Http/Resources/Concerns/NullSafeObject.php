<?php

namespace App\Http\Resources\Concerns;

use stdClass;

/**
 * Objek null-safe untuk item landing page yang datang sebagai array.
 *
 * `LandingResource` menerima payload dari controller apa adanya: bisa koleksi
 * model Eloquent, bisa array hasil `DB::table()`/query builder. Model Eloquent
 * mengembalikan `null` untuk kolom yang tidak ada, sementara `stdClass` biasa
 * melempar "Undefined property" (dan Laravel mengubahnya jadi ErrorException
 * 500). Subclass `stdClass` ini menyamakan perilakunya lewat `__get()`.
 *
 * Mewarisi `stdClass` juga membuat properti dinamis tetap legal di PHP 8.2+.
 */
final class NullSafeObject extends stdClass
{
    /**
     * Bungkus array asosiatif; nilai lain (model, objek, skalar) diteruskan.
     */
    public static function wrap(mixed $item): mixed
    {
        if (! is_array($item)) {
            return $item;
        }

        $object = new self;

        foreach ($item as $key => $value) {
            $object->{$key} = $value;
        }

        return $object;
    }

    public function __get(string $name): mixed
    {
        return null;
    }

    public function __isset(string $name): bool
    {
        return false;
    }

    /**
     * Tiru kontrak Eloquent: relasi dianggap "termuat" bila controller
     * menyertakan key-nya di payload, sehingga `whenLoaded()` tetap bekerja.
     */
    public function relationLoaded(string $key): bool
    {
        return property_exists($this, $key);
    }
}
