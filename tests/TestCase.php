<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as IlluminateTestCase;

class TestCase extends IlluminateTestCase
{
    use CreatesUsers;
    use HttpAssertions;

    protected function dispatch($job): void
    {
        $job->handle();
    }

    public function getStub(string $nameOfStub): string
    {
        return __DIR__ . "/stubs/{$nameOfStub}";
    }
}
