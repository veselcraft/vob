<?php declare(strict_types=1);
namespace vob\Web\Presenters;
use vob\Web\Models\Entities\Article;
use vob\Web\Models\Entities\Comment;
use vob\Web\Models\Repositories\Users;
use vob\Web\Models\Repositories\Articles;
use vob\Web\Models\Repositories\Comments;
use \Parsedown;

final class ArticlePresenter extends VOBPresenter
{
    private $articles;

    function __construct(Articles $articles)
    {
        $this->articles = $articles;

        parent::__construct();
    }

    function renderView(int $id): void
    {
        $article = $this->articles->get($id);

        if(!$article)
            $this->notFound();
        else  {
            $parse = new Parsedown();
            $this->template->article = $article;
            $this->template->author = (new Users)->get($article->getUserId());
            $this->template->art_content = $parse->text($article->getContent());
            $this->template->commentsCount = (new Comments)->getCommentsCountByTarget($article->getId());
            $this->template->comments = (new Comments)->getCommentsByTarget($article->getId(), (int) ($_GET["p"] ?? 1));
            $this->template->usersRepo = (new Users);
            $this->template->paginatorConf = (object) [
                "count"   => $this->template->commentsCount,
                "page"    => (int) ($_GET["p"] ?? 1),
                "amount"  => sizeof($this->template->comments),
                "perPage" => VOB_DEFAULT_PER_PAGE,
            ];
        }
    }

    function renderAllArticles(): void
    {
        $this->template->count   = $this->articles->getCountAllArticles();
        $this->template->articles = iterator_to_array($this->articles->getAllArticles((int) ($_GET["p"] ?? 1)));
        $this->template->paginatorConf = (object) [
            "count"   => $this->template->count,
            "page"    => (int) ($_GET["p"] ?? 1),
            "amount"  => sizeof($this->template->articles),
            "perPage" => VOB_DEFAULT_PER_PAGE,
        ];
    }

    function renderCreate(): void
    {
        if($_SERVER["REQUEST_METHOD"] === "POST") {

            if(!is_null($this->user))
                if($this->user->identity->getGroup() != 2)
                    $this->redirect("/", static::REDIRECT_TEMPORARY);

            $this->template->art_title = $this->postParam("title");
            $this->template->art_content = $this->postParam("content");
            
            if (empty($this->postParam("title")))
            {
                $this->flashFail("danger", tr("error_title_empty"));
            }

            if (empty($this->postParam("content")))
            {
                $this->flashFail("danger", tr("error_content_empty"));
            }

            $article = new Article;
            $article->setUser($this->user->identity->getId());
            $article->setTitle($this->postParam("title"));
            $article->setContent($this->postParam("content"));
            $article->setDate(time());
            $article->setDeleted(0);
            $article->setDraft(0);
            $article->save();
            
            $this->flash("success", tr("error_article_published"));
            $this->redirect("/articles", static::REDIRECT_TEMPORARY);
        }
    }
}