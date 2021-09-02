<?php

use App\Jobs\UpdateArticle;
use App\Models\Article;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

uses(TestCase::class);
uses(DatabaseMigrations::class);

test('we can update an article', function () {
    $user = $this->createUser();
    $article = Article::factory()->create(['author_id' => $user->id()]);

    $article = $this->dispatch(new UpdateArticle($article, 'Title', 'Body', false));

    $this->assertEquals('Title', $article->title());
    $this->assertEquals('Body', $article->body());
});

test('we can submit an existing article for approval', function () {
    $user = $this->createUser();
    $article = Article::factory()->create(['author_id' => $user->id()]);

    $article = $this->dispatch(new UpdateArticle($article, 'Title', 'Body', true));

    $this->assertNotNull($article->submittedAt());
});

test('we cannot update the submission time when saving changes', function () {
    $user = $this->createUser();
    $article = Article::factory()->create(['author_id' => $user->id(), 'submitted_at' => '2020-06-20 00:00:00']);

    $article = $this->dispatch(new UpdateArticle($article, 'Title', 'Body', false));

    $this->assertSame('2020-06-20 00:00:00', $article->submittedAt()->format('Y-m-d H:i:s'));
    $this->assertTrue($article->isNotPublished());
});
