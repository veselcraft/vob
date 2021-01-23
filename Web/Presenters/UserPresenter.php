<?php declare(strict_types=1);
namespace vob\Web\Presenters;
use vob\Web\Models\Entities\Article;
use vob\Web\Models\Repositories\Users;
use vob\Web\Models\Repositories\Articles;

final class UserPresenter extends VOBPresenter
{
    private $users;

    function __construct(Users $users)
    {
        $this->users = $users;

        parent::__construct();
    }

    function renderView(int $id): void
    {
        $user = $this->users->get($id);

        if(!$user)
            $this->notFound();
        else 
        {
            $this->template->user = $user;
            $this->template->count    = (new Articles)->getCountByUser($user->getId());
            $this->template->articles = (new Articles)->getByUser($user->getId(), (int) ($_GET["p"] ?? 1));
            $this->template->paginatorConf = (object) [
            "count"   => $this->template->count,
            "page"    => (int) ($_GET["p"] ?? 1),
            "amount"  => sizeof($this->template->articles),
            "perPage" => VOB_DEFAULT_PER_PAGE,
        ];
        }
    }

    private function checkURL(string $url): ?string
    {
        if(preg_match("@^(https?)://[^\s/$.?#].[^\s]*$@i", $url) == 1) 
        {
            $services = json_decode(file_get_contents(dirname(__FILE__) . "/../../services.json"));
            
            foreach ($services->services as $service) 
            {
                if(in_array(parse_url($url)['host'], $service->url)) 
                {
                    return $service->type;
                }
            }
        }
        return null;
    }

    function renderSettings(): void
    {
        $this->assertUserLoggedIn();

        if($_SERVER["REQUEST_METHOD"] === "POST") {
            $user = $this->user->identity;
            
            /*
             *  Main info
             */

            if(!empty($this->postParam("name")))
                $user->setName($this->postParam("name"));
            else
                $this->flashFail("danger", "Name cannot be empty.");
            $user->setBio($this->postParam("bio"));
            
            /*
             *  Password changing
             */

            if($this->postParam("old_password") && $this->postParam("new_password") && $this->postParam("repeat_password")) {
                if($this->postParam("new_password") === $this->postParam("repeat_password")) {
                    if(!$this->user->identity->getChandlerUser()->updatePassword($this->postParam("new_password"), $this->postParam("old_password")))
                        $this->flashFail("danger", "Old password does not match");
                } else {
                    $this->flashFail("danger", "New password does not match");
                }   
            }

            /*
             *  Avatar
             */

            if($_FILES["blob"]["error"] != 4) {
                $user->setAvatar($_FILES["blob"]);
            }

            $user->save();

            $this->flashFail("success", "Your settings was been saved.");
        }
    }
}