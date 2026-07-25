<?php

namespace App\Services;

class AuditActions
{
    // Auth
    const USER_LOGIN = 'user.login';

    const USER_LOGOUT = 'user.logout';

    // User management
    const USER_CREATED = 'user.created';

    const USER_UPDATED = 'user.updated';

    const USER_DELETED = 'user.deleted';

    const USER_SUSPENDED = 'user.suspended';

    const USER_UNSUSPENDED = 'user.unsuspended';

    const USER_PASSWORD_CHANGED = 'user.password_changed';

    // Role management
    const ROLE_CREATED = 'role.created';

    const ROLE_UPDATED = 'role.updated';

    const ROLE_DELETED = 'role.deleted';
}
