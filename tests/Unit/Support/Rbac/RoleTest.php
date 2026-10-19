<?php

use App\Support\Rbac\Permission;
use App\Support\Rbac\Role;

it('never grants permission to update immutable occupancy reports', function () {
    foreach (Role::cases() as $role) {
        expect($role->permissions())
            ->not->toContain(Permission::OccupancyReportUpdate);
    }
});

it('grants membership removal only to the owner', function () {
    expect(Role::Owner->permissions())
        ->toContain(Permission::MembershipRemove);

    foreach ([Role::Admin, Role::Manager, Role::Viewer] as $role) {
        expect($role->permissions())
            ->not->toContain(Permission::MembershipRemove);
    }
});

it('keeps viewer read-only', function () {
    foreach (Role::Viewer->permissions() as $permission) {
        expect(str_ends_with($permission->value, '.view'))->toBeTrue();
    }
});

it('only grants explicitly declared permissions', function () {
    foreach (Role::cases() as $role) {
        expect($role->permissionNames())
            ->toBe(array_map(
                static fn (Permission $permission): string => $permission->value,
                $role->permissions(),
            ));
    }
});

it('keeps role permissions unique', function () {
    foreach (Role::cases() as $role) {
        expect($role->permissionNames())
            ->toHaveCount(count(array_unique($role->permissionNames())));
    }
});
