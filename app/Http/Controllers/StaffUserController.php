<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\CentreScope;
use App\Support\StaffPermissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffUserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => StaffPermissions::payload($user));

        return response()->json(['data' => $users]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedPayload($request);

        $user = User::create($validated);

        return response()->json([
            'message' => 'Staff account created.',
            'user' => StaffPermissions::payload($user),
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $this->validatedPayload($request, $user->id);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Staff account updated.',
            'user' => StaffPermissions::payload($user->fresh()),
        ]);
    }

    private function validatedPayload(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($ignoreId),
            ],
            'password' => [$ignoreId ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(StaffPermissions::roles())],
            'study_centre' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($data['role'] === StaffPermissions::CENTRE_COORDINATOR) {
            $request->validate([
                'study_centre' => ['required', 'string', 'max:255'],
            ]);
        } else {
            $data['study_centre'] = null;
        }

        if (! empty($data['study_centre'])) {
            $catalogue = array_map(
                fn ($name) => CentreScope::normalize($name),
                CentreScope::catalogue()
            );
            if (! in_array(CentreScope::normalize($data['study_centre']), $catalogue, true)) {
                abort(response()->json([
                    'message' => 'Unknown study centre.',
                    'errors' => ['study_centre' => ['Choose a recognised study centre.']],
                ], 422));
            }
        }

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
