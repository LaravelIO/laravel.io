<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use App\Mail\ArticleApprovedEmail;
use App\Models\Article;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ArticleApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Article $article)
    {
    }

    public function via(User $user): array
    {
        return ['mail', 'database'];
    }

    public function toMail(User $user): MailMessage
    {
        return (new ArticleApprovedEmail($this->article))
            ->to($user->emailAddress(), $user->name());
    }

    public function toDatabase(User $user)
    {
        return [
            'type' => 'article_approved',
            'article_title' => $this->article->title(),
            'article_slug' => $this->article->slug(),
        ];
    }
}
