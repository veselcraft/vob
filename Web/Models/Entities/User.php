<?php declare(strict_types=1);
namespace vob\Web\Models\Entities;
use vob\Web\Models\RowModel;
use vob\Web\Models\Utils\MediaUtil;
use vob\Web\Models\Repositories\Users;
use vob\Web\Models\Repositories\Articles;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Image;
use Chandler\Database\DatabaseConnection;
use Chandler\Security\User as ChandlerUser;

class User extends RowModel
{
    protected $tableName = "users";
    
    function getId()
    {
        return $this->getRecord()->id;
    }

    function getChandlerGUID(): string
    {
        return $this->getRecord()->user;
    }
    
    function getChandlerUser(): ChandlerUser
    {
        return new ChandlerUser($this->getRecord()->ref("ChandlerUsers", "user"));
    }

    function getName()
    {
        return $this->getRecord()->name;
    }

    function getBio()
    {
        return $this->getRecord()->bio;
    }

    function getGroup()
    {
        return $this->getRecord()->group;
    }

    function getArticles()
    {
        return (new Articles)->getByUser($this->getId());
    }

    function setAvatar(array $file)
    {
        if($file["error"] !== UPLOAD_ERR_OK)
            throw new ISE("File uploaded is corrupted");
        
        $hash = hash_file("whirlpool", $file["tmp_name"]);
        $image = Image::fromFile($file["tmp_name"]);
        if(($image->height >= ($image->width * pi())) || ($image->width >= ($image->height * pi())))
            throw new ISE("Invalid layout: expected layout that matches (x, ?!>3x)");
        
        $image->save((new Utils\Media)->pathFromHash($hash, "jpg"), 92, Image::JPEG);

        $this->stateChanges("avatar", $hash);
    }

    function getAvatarURL(): string 
    {
        $hash = $this->getRecord()->avatar;
        if(isset($hash))
            return (new Utils\Media)->getURL($hash, "jpg");
        else
            return "/assets/packages/static/".CHANDLER_ROOT_CONF["rootApp"]."/img/camera_200.png";
    }
}
