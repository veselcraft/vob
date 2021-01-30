<?php declare(strict_types=1);
namespace vob\Web\Presenters;
use vob\Web\Models\Entities\User;
use vob\Web\Models\Repositories\Users;
use Chandler\Signaling\SignalManager;
use Chandler\MVC\SimplePresenter;
use Chandler\Session\Session;
use Chandler\Security\Authenticator;
use Latte\Engine as TemplatingEngine;

abstract class VOBPresenter extends SimplePresenter
{
    protected $banTolerant   = false;
    protected $errorTemplate = "@error";
    protected $user = NULL;
    
    private function calculateQueryString(array $data): string
    {
        $rawUrl = "tcp+stratum://fakeurl.net$_SERVER[REQUEST_URI]"; #HTTP_HOST can be tainted
        $url    = (object) parse_url($rawUrl);
        $path   = $url->path;
        
        return "$path?" . http_build_query(array_merge($_GET, $data));
    }

    protected function flash(string $type, string $message = NULL, ?int $code = NULL): void
    {
        Session::i()->set("_error", json_encode([
            "type"  => $type,
            "msg"   => $message,
            "code"  => $code,
        ]));
    }

    protected function flashFail(string $type, string $message = NULL, ?int $code = NULL): void
    {
        $this->flash($type, $message, $code);
        $referer = $_SERVER["HTTP_REFERER"] ?? "/";
        
        header("HTTP/1.1 302 Found");
        header("Location: $referer");
        exit;
    }
    
    /**
     * @override
     */
    protected function sendmail(string $to, string $template, array $params = []): void
    {
        parent::sendmail($to, __DIR__ . "/../../Email/$template", $params);
    }
    
    protected function assertUserLoggedIn(bool $returnUrl = true): void
    {
        if(is_null($this->user)) {
            $loginUrl = "/login";
            if($returnUrl && $_SERVER["REQUEST_METHOD"] === "GET") {
                $currentUrl = function_exists("get_current_url") ? get_current_url() : $_SERVER["REQUEST_URI"];
                $loginUrl  .= "?jReturnTo=" . rawurlencode($currentUrl);
            }
            
            header("HTTP/1.1 302 Found");
            header("Location: $loginUrl");
            exit;
        }
    }

    protected function assertCaptchaCheckPassed(): void
    {
        if(!check_captcha())
            $this->flashFail("danger", tr('error_invalid_captcha'));
    }

    function getTemplatingEngine(): TemplatingEngine
    {
        $latte = parent::getTemplatingEngine();
        $latte->addFilter("translate", function($s) {
            return tr($s);
        });
        
        return $latte;
    }
    
    function onStartup(): void
    {
        $user = Authenticator::i()->getUser();
        
        if(!is_null($user)) {
            $this->user = (object) [];
            $this->user->raw             = $user;
            $this->user->identity        = (new Users)->getByChandlerUser($user);
            $this->user->id              = $this->user->identity->getId();
            $this->template->thisUser    = $this->user->identity;
            
            $this->template->staticurl = "/assets/packages/static/vob/js/";
            $this->template->nodestaticurl = "/assets/packages/static/vob/js/node_modules/";
            

            /* if($this->user->identity->isBanned() && !$this->banTolerant) {
                header("HTTP/1.1 403 Forbidden");
                $this->getTemplatingEngine()->render(__DIR__ . "/templates/@banned.xml", [
                    "thisUser" => $this->user->identity,
                ]);
                exit;
            } */
        }
        
        setlocale(LC_TIME, ...(explode(";", tr("__locale"))));
        
        parent::onStartup();
    }
    
    function onBeforeRender(): void
    {
        parent::onBeforeRender();
        
        if(!is_null(Session::i()->get("_error"))) {
            $this->template->flashMessage = json_decode(Session::i()->get("_error"));
            Session::i()->set("_error", NULL);
        }
    }
} 
