<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\CreatesSocietyContext;

abstract class TestCase extends BaseTestCase
{
    use CreatesSocietyContext;

    /**
     * Stands in as the acting user for created_by/updated_by columns.
     *
     * Declared here rather than in each test class so the shared fixtures can rely on
     * it. Tests that need it to hold a specific role assign one in setUp().
     */
    protected User $systemUser;
}
