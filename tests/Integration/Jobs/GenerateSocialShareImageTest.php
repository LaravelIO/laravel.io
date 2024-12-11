<?php

use App\Jobs\GenerateSocialShareImage;
use App\Models\Article;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spatie\Pixelmatch\Pixelmatch;
use Tests\TestCase;

uses(TestCase::class);
uses(DatabaseMigrations::class);

test('social image is generated for articles', function () {
    $article = Article::factory()->create([
        'title' => 'This is an article to test social share image generation'
    ]);

    $generatedSocialShareImagePath = sys_get_temp_dir() . '/generated_social_share_temporary_image.png';

    file_put_contents(
        $generatedSocialShareImagePath,
        ((new GenerateSocialShareImage($article))->handle())->content()
    );

    expect(
        Pixelmatch::new(
            $generatedSocialShareImagePath,
            $this->getStub('generate_social_share_image.png')
        )->matches()
    )->toBeTrue();

    unlink($generatedSocialShareImagePath);
});
