<?php

namespace App\Constants;

class AppPermission
{
    const MANAGE_USERS = 'manage users';
    const MANAGE_BUSINESSES = 'manage businesses';
    const MANAGE_ROLES = 'manage roles';
    const MANAGE_PERMISSIONS = 'manage permissions';

    public static function all(): array
    {
        return [
            self::MANAGE_USERS,
            self::MANAGE_BUSINESSES,
            self::MANAGE_ROLES,
            self::MANAGE_PERMISSIONS,
        ];
    }

}
