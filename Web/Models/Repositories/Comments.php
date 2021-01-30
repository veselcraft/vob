<?php declare(strict_types=1);
namespace vob\Web\Models\Repositories;
use vob\Web\Models\Entities\User;
use vob\Web\Models\Entities\Comment;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Comments
{
    private $context;
    private $comments;

    function __construct()
    {
        $this->context  = DatabaseConnection::i()->getContext();
        $this->comments = $this->context->table("comments");
    }
    
    private function toComments(?ActiveRow $ar): ?Comments
    {
        return is_null($ar) ? NULL : new Comment($ar);
    }
    
    function get(int $id): ?Article
    {
        return $this->toComments($this->articles->get($id));
    }

    function getCommentsByTarget(int $postid, int $page, ?int $perPage = NULL): \Traversable
    {
        $comments = $this->comments->where([
            "postid"  => $postid,
            "deleted" => false,
        ])->order("date DESC")->page($page, $perPage ?? VOB_DEFAULT_PER_PAGE);
        
        return new Util\EntityStream("Comment", $comments);
    }
    
    function getCommentsCountByTarget(int $postid): int
    {
        return sizeof($this->comments->where([
            "postid"  => $postid,
            "deleted" => false,
        ]));
    }
    
    use \Nette\SmartObject;
}