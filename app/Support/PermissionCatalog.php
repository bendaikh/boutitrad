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

    /**
     * @return list<string>
     */
    public static function keysForGroups(array $groupKeys): array
    {
        $keys = [];

        foreach (config('permissions.groups', []) as $group) {
            if (! in_array($group['key'], $groupKeys, true)) {
                continue;
            }

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
     * @return list<string>
     */
    public static function allowedKeysForRole(string $role): array
    {
        return match ($role) {
            'commercial' => array_values(array_unique(array_merge(
                self::keysForGroups(['dashboard', 'clients', 'ventes', 'configuration']),
                ['stock.view', 'stock.print'],
            ))),
            'gestionnaire_stock' => self::keysForGroups(['dashboard', 'stock']),
            'livreur' => self::keysForGroups(['dashboard']),
            default => self::keys(),
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function groupsForRole(string $role): array
    {
        $allowedGroupKeys = match ($role) {
            'commercial' => ['dashboard', 'clients', 'ventes', 'stock', 'configuration'],
            'gestionnaire_stock' => ['dashboard', 'stock'],
            'livreur' => ['dashboard'],
            default => array_column(config('permissions.groups', []), 'key'),
        };

        $allowedKeys = self::allowedKeysForRole($role);

        return array_values(array_filter(
            array_map(
                fn (array $group) => self::filterGroupPermissions($group, $allowedKeys),
                array_filter(
                    config('permissions.groups', []),
                    fn (array $group) => in_array($group['key'], $allowedGroupKeys, true)
                )
            ),
            fn (array $group) => self::groupHasPermissions($group)
        ));
    }

    /**
     * @param  list<string>  $allowedKeys
     * @return array<string, mixed>
     */
    private static function filterGroupPermissions(array $group, array $allowedKeys): array
    {
        if (! empty($group['permissions'])) {
            $group['permissions'] = array_values(array_filter(
                $group['permissions'],
                fn (array $permission) => in_array($permission['key'], $allowedKeys, true)
            ));
        }

        if (! empty($group['sections'])) {
            $group['sections'] = array_values(array_filter(array_map(
                function (array $section) use ($allowedKeys) {
                    $section['permissions'] = array_values(array_filter(
                        $section['permissions'],
                        fn (array $permission) => in_array($permission['key'], $allowedKeys, true)
                    ));

                    return $section;
                },
                $group['sections']
            ), fn (array $section) => $section['permissions'] !== []));
        }

        return $group;
    }

    /**
     * @param  array<string, mixed>  $group
     */
    private static function groupHasPermissions(array $group): bool
    {
        if (! empty($group['permissions'])) {
            return true;
        }

        foreach ($group['sections'] ?? [] as $section) {
            if (! empty($section['permissions'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $permissions
     * @return list<string>
     */
    public static function sanitizeForRole(string $role, array $permissions): array
    {
        $allowedKeys = self::allowedKeysForRole($role);

        $sanitized = array_values(array_unique(array_filter(
            self::sanitize($permissions),
            fn (string $permission) => in_array($permission, $allowedKeys, true)
        )));

        if ($sanitized === []) {
            return self::defaultsForRole($role);
        }

        return $sanitized;
    }
}
