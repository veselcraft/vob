<?php declare(strict_types=1);
namespace vob\Web\Presenters;
use vob\Web\Models\Entities\Article;
use vob\Web\Models\Entities\Comment;
use vob\Web\Models\Entities\Utils\Media;
use vob\Web\Models\Repositories\Users;
use vob\Web\Models\Repositories\Articles;
use vob\Web\Models\Repositories\Comments;
use Nette\Utils\Image;
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
            if($article->isDraft()) {
                if(isset($this->user->identity)) {
                    if($this->user->identity->getId() != $article->getUserId()) {
                        $this->notFound();
                    }else{
                        $this->flash("info", tr("alert_this_is_draft"));
                    }
                }else{
                    $this->notFound();
                }
            }

            $this->template->article = $article;
            $this->template->author = (new Users)->get($article->getUserId());
            $this->template->art_content = (new Parsedown())->text($article->getContent());

            /* Preview stuff */

            $hash = md5($article->getTitle() 
            . "vob" 
            . $article->getId() 
            . VOB_ROOT_CONF['vob']['preview']['logo_url'] 
            . VOB_ROOT_CONF['vob']['appearance']['name']);


            if(empty($article->getPreview()) || $article->getPreview() != $hash) {
                // TODO: Parse dir to choose random image
                $previewColors = array("red", "green", "blue");
                $preview = Image::fromFile(VOB_ROOT . "/Web/static/img/preview/" . $previewColors[array_rand($previewColors)] . ".png");
                $logo = Image::fromFile(VOB_ROOT . "/Web/static" . VOB_ROOT_CONF['vob']['preview']['logo_url']);
                
                $preview->filledRectangle(0, 0, 156, 136, Image::rgb(255, 255, 255, 127));
                $logo->resize(75, 75);
                $preview->place($logo, 50, 50);

                $white = $preview->colorAllocate(255, 255, 255);
                $font = VOB_ROOT . "/Web/static" . VOB_ROOT_CONF['vob']['preview']['font_url'];

                $preview->ttfText(24, 0, 175, 100, $white, $font, VOB_ROOT_CONF['vob']['appearance']['name']);
                $preview->ttfText(46, 0, 45, 230, $white, $font, wordwrap($article->getTitle(), 25));
                $preview->save(Media::pathFromHash($hash, "jpg"));

                $article->setPreview($hash);
                $article->save();
            }

            $this->template->preview = Media::getURL($hash, "jpg");
            $this->template->usersRepo = (new Users);

            $this->template->commentsCount = (new Comments)->getCommentsCountByTarget($article->getId());
            $this->template->comments = (new Comments)->getCommentsByTarget($article->getId(), (int) ($_GET["p"] ?? 1));
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
        $this->template->articles = $this->articles->getAllArticles((int) ($_GET["p"] ?? 1));
        $this->template->paginatorConf = (object) [
            "count"   => $this->template->count,
            "page"    => (int) ($_GET["p"] ?? 1),
            "amount"  => sizeof($this->template->articles),
            "perPage" => VOB_DEFAULT_PER_PAGE,
        ];
    }

    function renderCreate(): void
    {
        $this->assertUserLoggedIn();

        if($this->user->identity->mayWriteAccessToArticles()) {
            if(!empty($this->postParam("id"))) {
                $article = $this->articles->get(intval($this->postParam("id")));

                $this->template->art_title = $article->getTitle();
                $this->template->art_content = $article->getContent();
                $this->template->id = $this->postParam("id");
            }

            if(!empty($this->postParam("check")) && $_SERVER["REQUEST_METHOD"] == "POST") {

                if(!is_null($this->user))
                    if($this->user->identity->getGroup() != 2)
                        $this->redirect("/", static::REDIRECT_TEMPORARY);

                $this->template->art_title = $this->postParam("title");
                $this->template->art_content = $this->postParam("content");
                
                if (empty($this->postParam("title")))
                    $this->flashFail("danger", tr("error_title_empty"));

                if (empty($this->postParam("content")))
                    $this->flashFail("danger", tr("error_content_empty"));

                $isDraft = $this->postParam("draft") == "1" ? true : false;

                $isEdit = !empty($this->postParam("id"));

                if($isEdit){
                    $article = $this->articles->get(intval($this->postParam("id")));

                    if($article->getUserId() != $this->user->identity->getId() && $this->user->identity->getGroup() != 2)
                        $this->flashError("danger", tr("error_you_are_not_editor"));
                }else{
                    $article = new Article;
                }
                $article->setUser($this->user->identity->getId());
                $article->setTitle($this->postParam("title"));
                $article->setContent($this->postParam("content"));

                if($isEdit) {
                    if(!$isDraft && $article->isDraft())
                        $article->setDate(time());
                    else
                        $article->setEdited_date(time());
                } else
                    $article->setDate(time());

                $article->setDeleted(0);

                if($isDraft) 
                    $article->setDraft(1);
                else
                    $article->setDraft(0);
                
                $article->save();
                
                if($isDraft){
                    $this->flash("success", tr("alert_article_published_this_is_draft"));
                    $this->redirect("/user" . $this->user->identity->getId() . "/drafts", static::REDIRECT_TEMPORARY);
                }else{
                    $this->flash("success", tr("error_article_published"));
                    $this->redirect("/articles", static::REDIRECT_TEMPORARY);
                }
            }
        } else {
            $this->flashFail("error", "You don't have access to edit or create articles");
        }
    }

    function renderComment(int $id): void
    {
        $this->assertUserLoggedIn();
        $this->assertCaptchaCheckPassed();

        if($_SERVER["REQUEST_METHOD"] == "POST") {
            if(!is_null($this->articles->get($id))) {
                if (empty($this->postParam("comment")))
                    $this->flashFail("danger", tr("error_content_empty"));
                else if (sizeof($this->postParam("comment")) > VOB_ROOT_CONF['vob']['preferences']['comments_symbols_limit'])
                    $this->flashFail("danger", tr("error_limit") . " (" . VOB_ROOT_CONF['vob']['preferences']['comments_symbols_limit'] . ")");
                
                $comment = new Comment;
                $comment->setUser($this->user->identity->getId());
                $comment->setPostid($id);
                $comment->setDate(time());
                $comment->setText($this->postParam("comment"));
                $comment->setDeleted(0);
                $comment->save();
                $this->flashFail("success", tr("alert_comment_published"));
            }
        } else {
            $this->flashFail("danger", "Хакеры? Интересно");
        }   // гагага это отсылка к овкк
    }
}