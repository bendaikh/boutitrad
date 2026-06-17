<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\Rule;

class CommercialEmail
{
    public const DOMAIN = '@beldimalaki.com';

    public static function normalize(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        return strtolower(trim($email));
    }

    public static function localPart(?string $email): string
    {
        if ($email === null || $email === '') {
            return '';
        }

        $email = self::normalize($email);

        if (str_ends_with($email, self::DOMAIN)) {
            return substr($email, 0, -strlen(self::DOMAIN));
        }

        return $email;
    }

    public static function fromInput(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $input = strtolower(trim($input));

        if ($input === '') {
            return '';
        }

        if (str_contains($input, '@')) {
            return self::normalize($input);
        }

        return self::normalize($input.self::DOMAIN);
    }

    /**
     * @return list<\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    public static function rules(?User $user = null): array
    {
        return [
            'required',
            'email',
            'max:255',
            Rule::unique('users', 'email')->ignore($user?->id),
            'ends_with:'.self::DOMAIN,
        ];
    }

    /**
     * @return list<\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    public static function rulesForRole(?User $user, ?string $role): array
    {
        $rules = [
            'required',
            'email',
            'max:255',
            Rule::unique('users', 'email')->ignore($user?->id),
        ];

        if ($role === UserRole::Commercial->value) {
            $rules[] = 'ends_with:'.self::DOMAIN;
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'email.ends_with' => 'L\'email doit se terminer par @beldimalaki.com.',
        ];
    }
}
