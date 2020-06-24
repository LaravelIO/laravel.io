<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Reply;
use App\Models\Thread;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AdminTest extends BrowserKitTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function requires_login()
    {
        $this->visit('/admin')
            ->seePageIs('/login');
    }

    /** @test */
    public function normal_users_cannot_visit_the_admin_section()
    {
        $this->login();

        $this->get('/admin')
            ->assertForbidden();
    }

    /** @test */
    public function admins_can_see_the_users_overview()
    {
        $this->loginAsAdmin();
        $this->assertCanSeeTheUserOverview();
    }

    /** @test */
    public function moderators_can_see_the_users_overview()
    {
        $this->loginAsModerator();
        $this->assertCanSeeTheUserOverview();
    }

    private function assertCanSeeTheUserOverview()
    {
        factory(User::class)->create(['name' => 'Freek Murze']);
        factory(User::class)->create(['name' => 'Frederick Vanbrabant']);

        $this->visit('/admin')
            ->see('Freek Murze')
            ->see('Frederick Vanbrabant');
    }

    /** @test */
    public function admins_can_ban_a_user()
    {
        $this->loginAsAdmin();
        $this->assertCanBanUsers();
    }

    /** @test */
    public function moderators_can_ban_a_user()
    {
        $this->loginAsModerator();
        $this->assertCanBanUsers();
    }

    private function assertCanBanUsers()
    {
        $user = factory(User::class)->create(['name' => 'Freek Murze']);

        $this->put('/admin/users/' . $user->username() . '/ban')
            ->assertRedirectedTo('/user/' . $user->username());

        $this->notSeeInDatabase('users', ['id' => $user->id(), 'banned_at' => null]);
    }

    /** @test */
    public function admins_can_unban_a_user()
    {
        $this->loginAsAdmin();
        $this->assertCanUnbanUsers();
    }

    /** @test */
    public function moderators_can_unban_a_user()
    {
        $this->loginAsModerator();
        $this->assertCanUnbanUsers();
    }

    private function assertCanUnbanUsers()
    {
        $user = factory(User::class)->create(['name' => 'Freek Murze', 'banned_at' => Carbon::now()]);

        $this->put('/admin/users/' . $user->username() . '/unban')
            ->assertRedirectedTo('/user/' . $user->username());

        $this->seeInDatabase('users', ['id' => $user->id(), 'banned_at' => null]);
    }

    /** @test */
    public function admins_cannot_ban_other_admins()
    {
        $this->loginAsAdmin();
        $this->assertCannotBanAdmins();
    }

    /** @test */
    public function moderators_cannot_ban_admins()
    {
        $this->loginAsModerator();
        $this->assertCannotBanAdmins();
    }

    /** @test */
    public function moderators_cannot_ban_other_moderators()
    {
        $this->loginAsModerator();
        $this->assertCannotBanModerators();
    }

    private function assertCannotBanAdmins()
    {
        $this->assertCannotBanUsersByType(User::ADMIN);
    }

    private function assertCannotBanModerators()
    {
        $this->assertCannotBanUsersByType(User::MODERATOR);
    }

    private function assertCannotBanUsersByType(int $type)
    {
        $user = factory(User::class)->create(['type' => $type]);

        $this->put('/admin/users/' . $user->username() . '/ban')
            ->assertForbidden();
    }

    /** @test */
    public function admins_can_delete_a_user()
    {
        $user = factory(User::class)->create(['name' => 'Freek Murze']);
        $thread = factory(Thread::class)->create(['author_id' => $user->id()]);
        factory(Reply::class)->create(['replyable_id' => $thread->id()]);
        factory(Reply::class)->create(['author_id' => $user->id()]);

        $this->loginAsAdmin();

        $this->delete('/admin/users/' . $user->username())
            ->assertRedirectedTo('/admin');

        $this->notSeeInDatabase('users', ['name' => 'Freek Murze']);

        // Make sure associated content is deleted.
        $this->notSeeInDatabase('threads', ['author_id' => $user->id()]);
        $this->notSeeInDatabase('replies', ['replyable_id' => $thread->id()]);
        $this->notSeeInDatabase('replies', ['author_id' => $user->id()]);
    }

    /** @test */
    public function admins_cannot_delete_other_admins()
    {
        $user = factory(User::class)->create(['type' => User::ADMIN]);

        $this->loginAsAdmin();

        $this->delete('/admin/users/' . $user->username())
            ->assertForbidden();
    }

    /** @test */
    public function moderators_cannot_delete_users()
    {
        $user = factory(User::class)->create();

        $this->loginAsModerator();

        $this->delete('/admin/users/' . $user->username())
            ->assertForbidden();
    }

    /** @test */
    public function admins_can_list_submitted_articles()
    {
        $submittedArticle = factory(Article::class)->create(['submitted_at' => now()]);
        $draftArticle = factory(Article::class)->create();
        $liveArticle = factory(Article::class)->create(['submitted_at' => now(), 'approved_at' => now()]);

        $this->loginAsAdmin();

        $this->get('admin/articles')
            ->see($submittedArticle->title())
            ->dontSee($draftArticle->title())
            ->dontSee($liveArticle->title());
    }

    /** @test */
    public function moderators_can_list_submitted_articles()
    {
        $submittedArticle = factory(Article::class)->create(['submitted_at' => now()]);
        $draftArticle = factory(Article::class)->create();
        $liveArticle = factory(Article::class)->create(['submitted_at' => now(), 'approved_at' => now()]);

        $this->loginAsModerator();

        $this->get('admin/articles')
            ->see($submittedArticle->title())
            ->dontSee($draftArticle->title())
            ->dontSee($liveArticle->title());
    }

    /** @test */
    public function users_cannot_list_submitted_articles()
    {
        $this->login();

        $this->get('admin/articles')
            ->assertForbidden();
    }

    /** @test */
    public function guests_cannot_list_submitted_articles()
    {
        $this->get('admin/articles')
            ->assertRedirectedTo('login');
    }

    /** @test */
    public function admins_can_view_submitted_articles()
    {
        $article = factory(Article::class)->create(['submitted_at' => now()]);

        $this->loginAsAdmin();

        $this->get("articles/{$article->slug()}")
            ->see('Awaiting Approval');
    }

    /** @test */
    public function admins_can_approve_articles()
    {
        $article = factory(Article::class)->create(['submitted_at' => now()]);

        $this->loginAsAdmin();

        $this->put("/admin/articles/{$article->slug()}/approve");

        $this->assertNotNull($article->fresh()->approvedAt());
    }

    /** @test */
    public function moderators_can_approve_articles()
    {
        $article = factory(Article::class)->create(['submitted_at' => now()]);

        $this->loginAsModerator();

        $this->put("/admin/articles/{$article->slug()}/approve");

        $this->assertNotNull($article->fresh()->approvedAt());
    }

    /** @test */
    public function users_cannot_approve_articles()
    {
        $article = factory(Article::class)->create(['submitted_at' => now()]);

        $this->login();

        $this->put("/admin/articles/{$article->slug()}/approve")
            ->assertForbidden();
    }

    /** @test */
    public function guests_cannot_approve_articles()
    {
        $article = factory(Article::class)->create(['submitted_at' => now()]);

        $this->put("/admin/articles/{$article->slug()}/approve")
            ->assertRedirectedTo('/login');

        $this->assertNull($article->fresh()->approvedAt());
    }

    /** @test */
    public function admins_can_disapprove_articles()
    {
        $article = factory(Article::class)->create(['submitted_at' => now(), 'approved_at' => now()]);

        $this->loginAsAdmin();

        $this->put("/admin/articles/{$article->slug()}/disapprove");

        $this->assertNull($article->fresh()->approvedAt());
    }

    /** @test */
    public function moderators_can_disapprove_articles()
    {
        $article = factory(Article::class)->create(['submitted_at' => now(), 'approved_at' => now()]);

        $this->loginAsModerator();

        $this->put("/admin/articles/{$article->slug()}/disapprove");

        $this->assertNull($article->fresh()->approvedAt());
    }

    /** @test */
    public function users_cannot_disapprove_articles()
    {
        $article = factory(Article::class)->create(['submitted_at' => now(), 'approved_at' => now()]);

        $this->login();

        $this->put("/admin/articles/{$article->slug()}/disapprove")
            ->assertForbidden();
    }

    /** @test */
    public function guests_cannot_disapprove_articles()
    {
        $article = factory(Article::class)->create(['submitted_at' => now(), 'approved_at' => now()]);

        $this->put("/admin/articles/{$article->slug()}/disapprove")
            ->assertRedirectedTo('/login');

        $this->assertNotNull($article->fresh()->approvedAt());
    }

    /** @test */
    public function admins_can_pin_articles()
    {
        $article = factory(Article::class)->create(['submitted_at' => now(), 'approved_at' => now()]);

        $this->loginAsAdmin();

        $this->put("/admin/articles/{$article->slug()}/pinned");

        $this->assertTrue($article->fresh()->isPinned());
    }

    /** @test */
    public function moderators_can_pin_articles()
    {
        $article = factory(Article::class)->create(['submitted_at' => now(), 'approved_at' => now()]);

        $this->loginAsModerator();

        $this->put("/admin/articles/{$article->slug()}/pinned");

        $this->assertTrue($article->fresh()->isPinned());
    }

    /** @test */
    public function users_cannot_pin_articles()
    {
        $article = factory(Article::class)->create(['submitted_at' => now(), 'approved_at' => now()]);

        $this->login();

        $this->put("/admin/articles/{$article->slug()}/pinned");

        $this->assertFalse($article->fresh()->isPinned());
    }

    /** @test */
    public function guests_cannot_pin_articles()
    {
        $article = factory(Article::class)->create(['submitted_at' => now(), 'approved_at' => now()]);

        $this->put("/admin/articles/{$article->slug()}/pinned");

        $this->assertFalse($article->fresh()->isPinned());
    }

    /** @test */
    public function admins_can_unpin_articles()
    {
        $article = factory(Article::class)->create([
            'submitted_at' => now(),
            'approved_at' => now(),
            'is_pinned' => true
        ]);

        $this->loginAsAdmin();

        $this->put("/admin/articles/{$article->slug()}/pinned");

        $this->assertFalse($article->fresh()->isPinned());
    }

    /** @test */
    public function moderators_can_unpin_articles()
    {
        $article = factory(Article::class)->create([
            'submitted_at' => now(),
            'approved_at' => now(),
            'is_pinned' => true
        ]);

        $this->loginAsModerator();

        $this->put("/admin/articles/{$article->slug()}/pinned");

        $this->assertFalse($article->fresh()->isPinned());
    }

    /** @test */
    public function users_cannot_unpin_articles()
    {
        $article = factory(Article::class)->create([
            'submitted_at' => now(),
            'approved_at' => now(),
            'is_pinned' => true
        ]);

        $this->login();

        $this->put("/admin/articles/{$article->slug()}/pinned");

        $this->assertTrue($article->fresh()->isPinned());
    }

    /** @test */
    public function guests_cannot_unpin_articles()
    {
        $article = factory(Article::class)->create([
            'submitted_at' => now(),
            'approved_at' => now(),
            'is_pinned' => true
        ]);

        $this->put("/admin/articles/{$article->slug()}/pinned");

        $this->assertTrue($article->fresh()->isPinned());
    }
}
