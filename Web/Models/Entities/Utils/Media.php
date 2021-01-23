<?php declare(strict_types=1);
namespace vob\Web\Models\Entities\Utils;

class Media
{
    function getBaseDir(): string
    {
        $uploadSettings = VOB_ROOT_CONF["vob"]["preferences"]["uploads"];
        if($uploadSettings["mode"] === "server" && $uploadSettings["server"]["kind"] === "cdn")
            return $uploadSettings["server"]["directory"];
        else
            return VOB_ROOT . "/storage/";
    }

    function pathFromHash(string $hash, string $fileExtension): string
    {
        $dir = Media::getBaseDir() . substr($hash, 0, 2);
        if(!is_dir($dir))
            mkdir($dir);
        
        return "$dir/$hash." . $fileExtension;
    }

    function getURL(string $hash, string $fileExtension): string
    {        
        switch(VOB_ROOT_CONF["vob"]["preferences"]["uploads"]["mode"]) {
            default:
            case "default":
            case "basic":
                return "http://" . $_SERVER['HTTP_HOST'] . "/blob_" . substr($hash, 0, 2) . "/$hash.$fileExtension";
            break;
            case "accelerated":
                return "http://" . $_SERVER['HTTP_HOST'] . "/datastore/$hash.$fileExtension";
            break;
            case "server":
                $settings = (object) VOB_ROOT_CONF["vob"]["preferences"]["uploads"]["server"];
                return (
                    $settings->protocol .
                    "://" . $settings->host .
                    $settings->path .
                    substr($hash, 0, 2) . "/$hash.$fileExtension"
                );
            break;
        }
    }
}