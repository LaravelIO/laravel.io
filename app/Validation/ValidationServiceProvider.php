<?php

namespace App\Validation;

use App\Validation\HttpImageRule;
use Illuminate\Support\ServiceProvider;
use Validator;

class ValidationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Validator::extend(DoesNotContainUrlRule::NAME, DoesNotContainUrlRule::class.'@validate');
        Validator::extend(PasscheckRule::NAME, PasscheckRule::class.'@validate');
        Validator::extend(SpamRule::NAME, SpamRule::class.'@validate');
        Validator::extend(HttpImageRule::NAME, HttpImageRule::class.'@validate');
    }
}
