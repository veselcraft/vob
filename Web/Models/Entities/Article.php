<?php declare(strict_types=1);
namespace vob\Web\Models\Entities;
use vob\Web\Models\RowModel;
use vob\Web\Models\Entities\Utils\Media;
use vob\Web\Models\Repositories\Users;
use vob\Web\Util\DateTime;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Article extends RowModel
{
    protected $tableName = "articles";
    
    function getId(): int
    {
        return $this->getRecord()->id;
    }

    function getTitle(): string
    {
        return $this->getRecord()->title;
    }

    function getContent(): string
    {
        return $this->getRecord()->content;
    }

    function getUserId(): int
    {
        return $this->getRecord()->user;
    }

    function getPublishDate(): DateTime
    {
        return new DateTime($this->getRecord()->date);
    }

    function getEditedDate(): DateTime
    {
        return new DateTime($this->getRecord()->edited_date);
    }

    function isEdited(): bool 
    {
        return $this->getRecord()->edited_date == 0 ? False : True;
    }

    function isDraft(): bool 
    {
        return $this->getRecord()->draft == 0 ? False : True;
    }

    function getPreview(): string
    {
        return $this->getRecord()->preview;
    }

    function getPreviewURL(): string
    {
        return Media::getURL($this->getRecord()->preview, "jpg");
    }

    function getPreviewURLNoText(): string
    {
        return Media::getURL($this->getRecord()->preview."_NOTEXT", "jpg");
    }
}
