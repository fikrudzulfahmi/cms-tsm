<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\Public\StaffResource;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;

final class StaffController extends Controller
{
    public function index(): JsonResponse
    {
        $staff = Staff::with('photo')->orderBy('sort')->get();

        return StaffResource::collection($staff)
            ->response()
            ->withHeaders(['Cache-Control' => 'public, max-age=300']);
    }
}
