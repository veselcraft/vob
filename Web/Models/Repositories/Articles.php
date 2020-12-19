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

    function getAllArticles(): \Traversable
    {
        $result = $this->articles->where("deleted", 0)->where("draft", 0);
        return new Util\EntityStream("Article", $result);
    }

    function getByUser(int $user): \Traversable
    {
        $result = $this->articles->where("user", $user);
        return new Util\EntityStream("Article", $result);
    }
    
    use \Nette\SmartObject;
}