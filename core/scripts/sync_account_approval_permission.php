<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Cache;

$basePath = is_file(__DIR__ . '/../vendor/autoload.php') ? dirname(__DIR__) : __DIR__;

require $basePath . '/vendor/autoload.php';

$app = require_once $basePath . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$permission = Permission::where('code', 'admin.users.account.requests.pending')->first();

if (!$permission) {
    $permission        = new Permission();
    $permission->code  = 'admin.users.account.requests.pending';
    $permission->name  = 'Users Account Requests Pending';
    $permission->group = 'ManageUsersController';
    $permission->save();
}

$seedCodes = [
    'admin.users.all',
    'admin.users.active',
    'admin.users.detail',
    'admin.users.profile.incomplete',
    'admin.users.profile.completed',
    'admin.users.banned',
    'admin.users.email.unverified',
    'admin.users.mobile.unverified',
    'admin.users.kyc.unverified',
    'admin.users.kyc.pending',
    'admin.users.notification.all',
];

$roles = Role::whereHas('permissions', function ($query) use ($seedCodes) {
    $query->whereIn('code', $seedCodes);
})->with('permissions')->get();

$updatedRoles = [];

foreach ($roles as $role) {
    if (!$role->permissions->contains('id', $permission->id)) {
        $role->permissions()->attach($permission->id);
        Cache::forget($role->name . '_permission');
        $updatedRoles[] = $role->name;
    }
}

Cache::forget('AllPermissions');

echo 'Permission ID: ' . $permission->id . PHP_EOL;
echo 'Roles updated: ' . count($updatedRoles) . PHP_EOL;

foreach ($updatedRoles as $roleName) {
    echo '- ' . $roleName . PHP_EOL;
}
