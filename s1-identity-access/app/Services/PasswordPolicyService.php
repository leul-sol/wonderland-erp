<?php

namespace App\Services;

use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordPolicyService
{
    public function isExpired(User $user): bool
    {
        $expiryDays = config('password_policy.expiry_days');

        if ($expiryDays <= 0 || $user->password_changed_at === null) {
            return false;
        }

        return $user->password_changed_at->addDays($expiryDays)->isPast();
    }

    public function assertValidNewPassword(User $user, string $password, bool $allowSameAsCurrent = false): void
    {
        $this->assertPasswordMeetsPolicy($password, $user, $allowSameAsCurrent);
    }

    public function assertPasswordMeetsPolicy(string $password, ?User $user = null, bool $allowSameAsCurrent = false): void
    {
        $errors = [];

        if (strlen($password) < config('password_policy.min_length')) {
            $errors['password'][] = 'Password must be at least '.config('password_policy.min_length').' characters.';
        }

        if (config('password_policy.require_upper') && ! preg_match('/[A-Z]/', $password)) {
            $errors['password'][] = 'Password must include an uppercase letter.';
        }

        if (! preg_match('/[0-9]/', $password)) {
            $errors['password'][] = 'Password must include a number.';
        }

        if (! preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors['password'][] = 'Password must include a symbol.';
        }

        if ($user !== null && $user->exists) {
            $recent = PasswordHistory::query()
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit(config('password_policy.history'))
                ->pluck('password');

            foreach ($recent as $hash) {
                if (Hash::check($password, $hash)) {
                    $errors['password'][] = 'Password was used recently and cannot be reused.';
                    break;
                }
            }

            if (! $allowSameAsCurrent && Hash::check($password, $user->password)) {
                $errors['password'][] = 'Password must differ from the current password.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function recordPasswordChange(User $user, string $plainPassword): void
    {
        PasswordHistory::query()->create([
            'user_id' => $user->id,
            'password' => $user->password,
        ]);

        $keep = PasswordHistory::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->skip(config('password_policy.history'))
            ->pluck('id');

        if ($keep->isNotEmpty()) {
            PasswordHistory::query()->whereIn('id', $keep)->delete();
        }

        $user->password = Hash::make($plainPassword);
        $user->password_changed_at = now();
        $user->must_change_password = false;
        $user->save();
    }
}
