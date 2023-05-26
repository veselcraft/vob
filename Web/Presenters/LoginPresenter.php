<?php declare(strict_types=1);
namespace vob\Web\Presenters;
use vob\Web\Models\Entities\User;
use vob\Web\Models\Repositories\Users;
use Chandler\Session\Session;
use Chandler\Security\User as ChandlerUser;
use Chandler\Security\Authenticator;
use Chandler\Database\DatabaseConnection;

final class LoginPresenter extends VOBPresenter
{
    private $authenticator;
    private $db;
    private $users;
    private $restores;
    
    function __construct(Users $users)
    {
        $this->authenticator = Authenticator::i();
        $this->db = DatabaseConnection::i()->getContext();
        
        $this->users    = $users;
        
        parent::__construct();
    }

    private function emailValid(string $email): bool
    {
        if(empty($email)) return false;
        
        $email = trim($email);
        [$user, $domain] = explode("@", $email);
        $domain = idn_to_ascii($domain) . ".";
        
        return checkdnsrr($domain, "MX");
    }

    function renderRegistration(): void
    {
        if(!is_null($this->user))
            $this->redirect("/", static::REDIRECT_TEMPORARY);

        if(VOB_ROOT_CONF['vob']['preferences']['disableRegistrations'] == true)
            $this->flashFail("danger", tr("error_not_allowed_for_registrations"));

        $this->assertCaptchaCheckPassed();
        
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            if(!$this->emailValid($this->postParam("email")))
                $this->flashFail("danger", tr("error_email_not_correct"));
            
            $chUser = ChandlerUser::create($this->postParam("email"), $this->postParam("password"));
            if(!$chUser)
                $this->flashFail("danger", tr("error_user_registred"));

            $user = new User;
            $user->setUser($chUser->getId());
            $user->setName($this->postParam("name"));
            if (VOB_ROOT_CONF['vob']['preferences']['use_email'])
                $user->setConfirm(bin2hex(random_bytes(32)));
            $user->save();
            
            $this->authenticator->authenticate($chUser->getId());
            $this->flash("success", tr("alert_registred"));
            $this->redirect("/", static::REDIRECT_TEMPORARY);
        }
    }
    
    function renderLogin(): void
    {
        if(!is_null($this->user))
            $this->redirect("/", static::REDIRECT_TEMPORARY);
        
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            
            $user = $this->db->table("ChandlerUsers")->where("login", $this->postParam("email"))->fetch();
            if(!$user)
                $this->flashFail("danger", "Incorrect Email or Password.");
            
            if(!$this->authenticator->login($user->id, $this->postParam("password")))
                $this->flashFail("danger", "Incorrect Email or Password.");
            
            $redirUrl = $_GET["jReturnTo"] ?? "/";
            $this->flash("success", "You are logged in.");
            $this->redirect($redirUrl, static::REDIRECT_TEMPORARY);
            exit;
        }
    }
    
    function renderLogout(): void
    {
        $this->assertUserLoggedIn();
        $this->authenticator->logout();
        Session::i()->set("_su", NULL);
        
        $this->redirect("/", static::REDIRECT_TEMPORARY_PRESISTENT);
    }
}