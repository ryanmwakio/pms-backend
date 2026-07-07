<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function listForWorkspace(Workspace $workspace): Collection
    {
        return $workspace->members()->orderBy('name')->get();
    }

    public function find(int $id): User
    {
        return User::findOrFail($id);
    }

    public function updateProfile(User $user, array $data): User
    {
        // Generate initials if name changes
        if (isset($data['name'])) {
            $parts = explode(' ', trim($data['name']));
            $data['avatar_initials'] = strtoupper(
                (substr($parts[0], 0, 1)).(isset($parts[1]) ? substr($parts[1], 0, 1) : '')
            );
        }

        $user->update($data);

        return $user->fresh();
    }

    public function updatePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($newPassword)]);
    }
}
