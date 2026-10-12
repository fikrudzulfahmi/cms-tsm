<?php

namespace App\Http\Controllers\Api\V1\Admin\Concerns;

use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait ReordersItems
{
    /**
     * Reorder items based on array of IDs {ids: [id1, id2, ...]}.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer'],
        ]);

        $modelClass = $this->modelClass();

        DB::transaction(function () use ($validated, $modelClass) {
            foreach ($validated['ids'] as $sort => $id) {
                $modelClass::where('id', $id)->update(['sort' => $sort + 1]);
            }
        });

        $modelName = class_basename($modelClass);
        ActivityLogger::log(
            $request->user()?->id,
            'admin.' . strtolower($modelName) . 's.reorder',
            $modelName,
            null,
            ['count' => count($validated['ids'])]
        );

        return response()->json([
            'message' => 'Urutan berhasil diperbarui.',
        ]);
    }
}
