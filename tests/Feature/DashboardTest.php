<?php

namespace Tests\Feature;

use App\Http\Livewire\NotificationCount;
use App\Http\Livewire\Notifications;
use App\Models\Reply;
use App\Models\Thread;
use App\Notifications\NewReplyNotification;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Livewire;

class DashboardTest extends BrowserKitTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function requires_login()
    {
        $this->visit('/dashboard')
            ->seePageIs('/login');
    }

    /** @test */
    public function users_can_see_some_statistics()
    {
        $user = $this->createUser();
        $thread = factory(Thread::class, 3)->create(['author_id' => $user->id()])->first();
        $reply = factory(Reply::class, 2)->create([
            'author_id' => $user->id(),
            'replyable_id' => $thread->id(),
        ])->first();

        $thread->markSolution($reply);

        $this->loginAs($user);

        $this->visit('/dashboard')
            ->see('3 threads')
            ->see('2 replies')
            ->see('1 solution');
    }

    /** @test */
    public function users_can_see_notifications()
    {
        $this->markTestSkipped('Skipped until Livewire v1.0.13 is released.');

        $userOne = $this->createUser();

        $thread = factory(Thread::class)->create(['author_id' => $userOne->id()]);
        $reply = factory(Reply::class)->create(['replyable_id' => $thread->id()]);

        $userOne->notifications()->create([
            'id' => Str::random(),
            'type' => NewReplyNotification::class,
            'data' => [
                'type' => 'new_reply',
                'reply' => $reply->id(),
                'replyable_id' => $reply->replyable_id,
                'replyable_type' => $reply->replyable_type,
                'replyable_subject' => $reply->replyAble()->replyAbleSubject(),
            ],
        ]);

        $replyAbleRoute = route('replyable', [$reply->replyable_id, $reply->replyable_type]);

        $this->loginAs($userOne);

        Livewire::test(Notifications::class)
            ->assertSee(new HtmlString(
                "A new reply was added to <a href=\"{$replyAbleRoute}\" class=\"text-green-darker\">\"{$thread->subject()}\"</a>."
            ));
    }

    /** @test */
    public function users_can_mark_notifications_as_read()
    {
        $this->markTestSkipped('Skipped until Livewire v1.0.13 is released.');

        $userOne = $this->createUser();

        $thread = factory(Thread::class)->create(['author_id' => $userOne->id()]);
        $reply = factory(Reply::class)->create(['replyable_id' => $thread->id()]);

        $notification = $userOne->notifications()->create([
            'id' => Str::random(),
            'type' => NewReplyNotification::class,
            'data' => [
                'type' => 'new_reply',
                'reply' => $reply->id(),
                'replyable_id' => $reply->replyable_id,
                'replyable_type' => $reply->replyable_type,
                'replyable_subject' => $reply->replyAble()->replyAbleSubject(),
            ],
        ]);

        $replyAbleRoute = route('replyable', [$reply->replyable_id, $reply->replyable_type]);

        $this->loginAs($userOne);

        Livewire::test(Notifications::class)
            ->assertSee(new HtmlString(
                "A new reply was added to <a href=\"{$replyAbleRoute}\" class=\"text-green-darker\">\"{$thread->subject()}\"</a>."
            ))
            ->call('markAsRead', $notification->id)
            ->assertDontSee(new HtmlString(
                "A new reply was added to <a href=\"{$replyAbleRoute}\" class=\"text-green-darker\">\"{$thread->subject()}\"</a>."
            ))
            ->assertEmitted('NotificationMarkedAsRead');
    }

    /** @test */
    public function a_non_logged_in_user_cannot_access_notifications()
    {
        Livewire::test(Notifications::class)
            ->assertForbidden();
    }

    /** @test */
    public function a_user_cannot_mark_other_users_notifications_as_read()
    {
        $userOne = $this->createUser();
        $userTwo = $this->createUser([
            'name' => 'Jane Doe',
            'username' => 'janedoe',
            'email' => 'jane@example.com',
        ]);

        $thread = factory(Thread::class)->create(['author_id' => $userOne->id()]);
        $reply = factory(Reply::class)->create([
            'author_id' => $userTwo->id(),
            'replyable_id' => $thread->id(),
        ]);

        $notification = $userOne->notifications()->create([
            'id' => Str::random(),
            'type' => NewReplyNotification::class,
            'data' => [
                'type' => 'new_reply',
                'reply' => $reply->id(),
                'replyable_id' => $reply->replyable_id,
                'replyable_type' => $reply->replyable_type,
                'replyable_subject' => $reply->replyAble()->replyAbleSubject(),
            ],
        ]);

        $this->loginAs($userTwo);

        Livewire::test(Notifications::class)
            ->call('markAsRead', $notification->id)
            ->assertForbidden();
    }

    /** @test */
    public function a_user_sees_the_correct_number_of_notifications()
    {
        $userOne = $this->createUser();
        $userTwo = $this->createUser([
            'name' => 'Jane Doe',
            'username' => 'janedoe',
            'email' => 'jane@example.com',
        ]);

        $thread = factory(Thread::class)->create(['author_id' => $userOne->id()]);
        $reply = factory(Reply::class)->create([
            'author_id' => $userTwo->id(),
            'replyable_id' => $thread->id(),
        ]);

        for ($i = 0; $i < 10; $i++) {
            $userOne->notifications()->create([
                'id' => Str::random(),
                'type' => NewReplyNotification::class,
                'data' => [
                    'type' => 'new_reply',
                    'reply' => $reply->id(),
                    'replyable_id' => $reply->replyable_id,
                    'replyable_type' => $reply->replyable_type,
                    'replyable_subject' => $reply->replyAble()->replyAbleSubject(),
                ],
            ]);
        }

        $this->loginAs($userOne);

        Livewire::test(NotificationCount::class)
            ->assertSee('10');
    }
}
