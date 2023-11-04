<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateSocialShareImage;
use App\Models\Article;

class SocialImageController extends Controller
{
    public function __invoke(GenerateSocialShareImage $generateImage, Article $article)
    {
        return $this->dispatchSync(new $generateImage($article));
    }
}
