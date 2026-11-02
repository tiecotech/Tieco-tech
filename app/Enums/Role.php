<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Editor = 'editor';
    case Viewer = 'viewer';

    /** @return list<string> */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => ['*'],
            self::Manager => ['requests.view', 'requests.manage', 'requests.export', 'quotes.manage', 'messages.view', 'messages.manage'],
            self::Editor => ['content.manage', 'messages.view'],
            self::Viewer => ['requests.view', 'messages.view'],
        };
    }

    public function can(string $permission): bool
    {
        $all = $this->permissions();

        return in_array('*', $all, true) || in_array($permission, $all, true);
    }
}
