<?php

namespace Tests\Concerns;

use App\Models\Flat;
use App\Models\Role;
use App\Models\Society;
use App\Models\Tower;
use App\Models\User;

/**
 * Shared fixtures for tenant-aware feature tests.
 *
 * These four helpers were previously copy-pasted as private methods into nine separate
 * test files, with three different signatures between them. They live here now; the
 * signatures below are the superset, so every previous call site keeps working:
 *
 *  - createSocietyContext() returns [$society, $tower]. Callers destructuring just
 *    [$society] still work — the extra element is ignored.
 *  - createUserWithRole() takes a nullable Society and an optional Flat. No existing
 *    caller passed the Flat, so attaching it when supplied is additive.
 *
 * Consuming classes are expected to set $this->systemUser in setUp(); if they do not,
 * one is created on demand.
 */
trait CreatesSocietyContext
{
    /**
     * Create a society plus its first tower.
     *
     * @return array{0: Society, 1: Tower}
     */
    protected function createSocietyContext(string $prefix): array
    {
        $society = Society::create([
            'name' => strtoupper($prefix) . ' Society',
            'code' => strtoupper($prefix) . '-' . fake()->unique()->numerify('###'),
            'address' => fake()->address(),
            'city' => 'Indore',
            'state' => 'MP',
            'pincode' => '452001',
            'contact_number' => '900000' . fake()->unique()->numerify('####'),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
            'created_by' => $this->systemUserId(),
        ]);

        $tower = Tower::create([
            'society_id' => $society->id,
            'name' => strtoupper($prefix) . '-T1',
            'total_floors' => 10,
            'is_active' => true,
            'created_by' => $this->systemUserId(),
        ]);

        return [$society, $tower];
    }

    /**
     * Create a flat in the society's first tower, creating that tower if absent.
     */
    protected function createFlatForSociety(Society $society, string $flatNumber, array $overrides = []): Flat
    {
        $tower = $society->towers()->first() ?? Tower::create([
            'society_id' => $society->id,
            'name' => 'A',
            'total_floors' => 10,
            'is_active' => true,
            'created_by' => $this->systemUserId(),
        ]);

        return Flat::create(array_merge([
            'tower_id' => $tower->id,
            'flat_number' => $flatNumber,
            'floor_number' => 3,
            'type' => '2BHK',
            'carpet_area' => 900,
            'occupancy_status' => 'occupied',
            'is_active' => true,
            'created_by' => $this->systemUserId(),
        ], $overrides));
    }

    /**
     * Create a user, assign a role, and optionally make them an active resident of a flat.
     */
    protected function createUserWithRole(string $roleSlug, ?Society $society = null, ?Flat $flat = null): User
    {
        $user = User::factory()->create([
            'society_id' => $society?->id,
            'created_by' => $this->systemUserId(),
            'updated_by' => $this->systemUserId(),
        ]);

        $this->assignRole($user, $roleSlug);

        if ($flat) {
            $this->attachResidentToFlat($user, $flat);
        }

        return $user;
    }

    /**
     * Attach a user to a flat as an active resident.
     */
    protected function attachResidentToFlat(User $user, Flat $flat, array $pivot = []): void
    {
        $flat->residents()->syncWithoutDetaching([
            $user->id => array_merge([
                'relation_type' => 'owner',
                'start_date' => now()->subYear(),
                'is_primary' => true,
                'is_active' => true,
                'created_by' => $this->systemUserId(),
            ], $pivot),
        ]);
    }

    /**
     * Attach a role by slug.
     *
     * flushAccessCache() matters here: User memoizes role and permission slugs per
     * request, so a user whose roles are read before assignment would otherwise keep
     * answering from the stale memo for the rest of the test.
     */
    protected function assignRole(User $user, string $roleSlug): void
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);
        $user->flushAccessCache();
    }

    /**
     * ID used for created_by/updated_by columns.
     */
    protected function systemUserId(): int
    {
        if (!isset($this->systemUser)) {
            $this->systemUser = User::factory()->create([
                'created_by' => null,
                'updated_by' => null,
                'society_id' => null,
            ]);
        }

        return $this->systemUser->id;
    }
}
