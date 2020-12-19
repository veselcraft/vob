<?php declare(strict_types=1);
namespace vob\Web\Presenters;
use Chandler\Session\Session;

final class AboutPresenter extends VOBPresenter
{
    function renderIndex(): void
    {
        if(!is_null($_GET['lg'])){
            Session::i()->set("lang", $_GET['lg']);
        }

        if(VOB_ROOT_CONF['vob']['preferences']['badgemode'] == false)
            $this->pass("vob!Article->allArticles");
    }
    
}
