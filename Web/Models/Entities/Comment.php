<?php declare(strict_types=1);
namespace vob\Web\Models\Entities;
use vob\Web\Models\RowModel;
use vob\Web\Models\Repositories\Users;
use vob\Web\Util\DateTime;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Comment extends RowModel
{
    protected $tableName = "comments";
    
    function getId(): int
    {
        return $this->getRecord()->id;
    }

    function getArticle(): int
    {
        return $this->getRecord()->article;
    }

    function getUserId(): int
    {
        return $this->getRecord()->user;
    }

    function getText(): string
    {
        return $this->getRecord()->text;
    }

    function getPublishDate(): DateTime
    {
        return new DateTime($this->getRecord()->date);
    }
}
