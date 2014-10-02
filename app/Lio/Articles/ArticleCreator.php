<?php namespace Lio\Articles;
use Lio\Accounts\User;
use Lio\Articles\ArticleCreatorListener;

/**
* This class can call the following methods on the observer object:
*
* articleValidationError($errors)
* articleCreated($thread)
*/
class ArticleCreator
{
    protected $articles;

    public function __construct(ArticleRepository $articles)
    {
        $this->articles = $articles;
    }

    public function create(ArticleCreatorListener $listener, array $data, User $author, $validator = null)
    {
        if ($validator && ! $validator->isValid()) {
            return $listener->articleCreationError($validator->getErrors());
        }

        return $this->createArticle($listener, $data + ['author_id' => $author->id]);
    }

    protected function createArticle(ArticleCreatorListener $listener, $data)
    {
        $article = $this->articles->getNew($data);
        if ( ! $this->articles->save($article)) {
            return $listener->articleCreationError($article->getErrors());
        }
        return $listener->articleCreated($article);
    }
}
