<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\BrowserKitTestCase;

class ProfileTest extends BrowserKitTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function anyone_can_see_a_user_profile()
    {
        $this->createUser();

        $this->visit('/user/johndoe')
            ->see('John Doe');
    }
}
