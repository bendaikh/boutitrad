<?php

namespace App\Support;

class PermissionCatalog
{
    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        $keys = [];

        foreach (config('permissions.groups', []) as $group) {
            foreach ($group['permissions'] ?? [] as $permission) {
                $keys[] = $permission['key'];
            }

            foreach ($group['sections'] ?? [] as $section) {
                foreach ($section['permissions'] as $permission) {
                    $keys[] = $permission['key'];
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return list<array{key: string, label: string, short: string}>
     */
    public static function flatForUi(): array
    {
        $items = [];

        foreach (config('permissions.groups', []) as $group) {
            foreach ($group['permissions'] ?? [] as $permission) {
                $items[] = $permission;
            }

            foreach ($group['sections'] ?? [] as $section) {
                foreach ($section['permissions'] as $permission) {
                    $items[] = $permission;
                }
            }
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    public static function defaultsForRole(string $role): array
    {
        return config("permissions.defaults.{$role}", []);
    }

    /**
     * @param  list<string>  $permissions
     * @return list<string>
     */
    public static function sanitize(array $permissions): array
    {
        $allowed = static::keys();

        return array_values(array_unique(array_filter(
            $permissions,
            fn (string $permission) => in_array($permission, $allowed, true)
        )));
    }
}
