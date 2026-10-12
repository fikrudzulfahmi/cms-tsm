<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\Role;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Manajemen pengguna admin (spec 6.1). Hanya Super Admin.
 */
final class UserController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger)
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->input('q'), fn ($q, $s) => $q->where('name', 'like', '%' . $s . '%')->orWhere('email', 'like', '%' . $s . '%'))
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique(User::class)],
            'password' => ['required', 'string', 'min:8', 'max:72'],
            'role' => ['required', 'string', Rule::in(array_column(Role::cases(), 'value'))],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        $this->logger->log($request->user()?->id, 'admin.user.create', User::class, $user->id, ['email' => $user->email]);

        return response()->json(['data' => $user], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'required', 'email', 'max:160', Rule::unique(User::class)->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:72'],
            'role' => ['sometimes', 'required', 'string', Rule::in(array_column(Role::cases(), 'value'))],
            'is_active' => ['boolean'],
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        $this->logger->log($request->user()?->id, 'admin.user.update', User::class, $user->id);

        return response()->json(['data' => $user]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Pengguna dihapus.']);
    }
}
