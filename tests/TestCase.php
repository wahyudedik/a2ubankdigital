<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Indicates whether the default seeding should be performed before each test.
     *
     * @var bool
     */
    protected $seed = false;
}
