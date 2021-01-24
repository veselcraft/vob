<?php declare(strict_types=1);
namespace vob\Web\Models\Repositories;
use vob\Web\Models\Entities\User;
use vob\Web\Models\Entities\Article;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Articles
{
    private $context;
    private $articles;

    function __construct()
    {
        $this->context  = DatabaseConnection::i()->getContext();
        $this->articles = $this->context->table("articles");
    }
    
    private function toArticles(?ActiveRow $ar): ?Article
    {
        return is_null($ar) ? NULL : new Article($ar);
    }
    
    function get(int $id): ?Article
    {
        return $this->toArticles($this->articles->get($id));
    }

    function getCountAllArticles(): int
    {
        return sizeof($this->articles->where("deleted", 0)->where("draft", 0));
    }

    function getAllArticles(int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $result = $this->articles->where("deleted", 0)->where("draft", 0)->order("date DESC")->page($page, $perPage ?? VOB_DEFAULT_PER_PAGE);
        return new Util\EntityStream("Article", $result);
    }

    function getCountByUser(int $user): int
    {
        return sizeof($this->articles->where("user", $user)->where("deleted", 0)->where("draft", 0));
    }

    function getByUser(int $user, int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $result = $this->articles->where("user", $user)->where("deleted", 0)->where("draft", 0)->order("date DESC")->page($page, $perPage ?? VOB_DEFAULT_PER_PAGE);
        return new Util\EntityStream("Article", $result);
    }

    function getDraftCountByUser(int $user): int
    {
        return sizeof($this->articles->where("user", $user)->where("deleted", 0)->where("draft", 1));
    }

    function getDraftByUser(int $user, int $page = 1, ?int $perPage = NULL): \Traversable
    {
        $result = $this->articles->where("user", $user)->where("deleted", 0)->where("draft", 1)->order("date DESC")->page($page, $perPage ?? VOB_DEFAULT_PER_PAGE);
        return new Util\EntityStream("Article", $result);
    }

    function find(string $query): \Traversable
    {
        $query   = "%$query%";
        $perPage = $perPage ?? VOB_DEFAULT_PER_PAGE;
        $result  = $this->articles->where("deleted", 0)->where("draft", 0)->where("CONCAT_WS(' ', title, content) LIKE ?", $query);
        
        return new Util\EntityStream("Article", $result);
    }
    
    use \Nette\SmartObject;
}