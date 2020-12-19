<?php declare(strict_types=1);
use Chandler\Database\DatabaseConnection;
use Chandler\Session\Session;
use vob\Web\Util\Bitmask;
use vob\Web\Util\Localizator;

function _check_environment(): void
{
    $problems = [];
    if(file_exists(__DIR__ . "/update.pid"))
        $problems[] = "VOB is updating";
    
    if(!version_compare(PHP_VERSION, "7.3.0", ">="))
        $problems[] = "Incompatible PHP version: " . PHP_VERSION . " (7.3+ required, 7.4+ recommended)";
    
    $requiredExtensions = [
        "gd",
        "fileinfo",
        "PDO",
        "pdo_mysql",
        "pcre",
        "hash",
        "curl",
        "Core",
        "iconv",
        "mbstring",
        "sodium",
        "openssl",
        "json",
        "tokenizer",
        "libxml",
        "date",
        "session",
        "SPL",
    ];
    if(sizeof($missingExtensions = array_diff($requiredExtensions, get_loaded_extensions())) > 0)
        foreach($missingExtensions as $extension)
            $problems[] = "Missing extension $extension";
    
    if(sizeof($problems) > 0) {
        require __DIR__ . "/misc/install_err.phtml";
        exit;
    }
}

function strftime_safe(string $format, ?int $timestamp = NULL): string
{
    $str = strftime($format, $timestamp ?? time());
    if(PHP_SHLIB_SUFFIX === "dll") {
        $enc = tr("__WinEncoding");
        if($enc === "@__WinEncoding")
            $enc = "Windows-1251";
        
        $nStr = iconv($enc, "UTF-8", $str);
        if(!is_null($nStr))
            $str = $nStr;
    }
    
    return $str;
}

function tr(string $stringId, ...$variables): string 
{
    $localizer = Localizator::i();
    $lang      = Session::i()->get("lang", VOB_ROOT_CONF['vob']['preferences']['standard_language']);
    $output    = $localizer->_($stringId, $lang);

    if(sizeof($variables) > 0) {
        if(gettype($variables[0]) === "integer") {
            $numberedStringId = NULL;
            $cardinal         = $variables[0];
            switch($cardinal) {
                case 0:
                    $numberedStringId = $stringId . "_zero";
                break;
                case 1:
                    $numberedStringId = $stringId . "_one";
                break;
                default:
                    $numberedStringId = $stringId . ($cardinal < 5 ? "_few" : "_other");
            }
            
            $newOutput = $localizer->_($numberedStringId, $lang);
            if($newOutput === "@$numberedStringId") {
                $newOutput = $localizer->_($stringId . "_other", $lang);
                if($newOutput === ("@" . $stringId . "_other"))
                    $newOutput = $output;
            }

            $output = $newOutput;
        }
        
        for($i = 0; $i < sizeof($variables); $i++)
            $output = preg_replace("%(?<!\\\\)(\\$)" . ($i + 1) . "%", $variables[$i], $output);
    }
    
    return $output;
}

return (function() {
    _check_environment();
    require __DIR__ . "/vendor/autoload.php";
    
    setlocale(LC_TIME, "POSIX");
    
    if(empty($_SERVER["REQUEST_SCHEME"]))
        $_SERVER["REQUEST_SCHEME"] = empty($_SERVER["HTTPS"]) ? "HTTP" : "HTTPS";

    $showCommitHash = true; # plz remove when release
    if(is_dir($gitDir = VOB_ROOT . "/.git") && $showCommitHash)
        $ver = trim(`git --git-dir="$gitDir" log --pretty="%h" -n1 HEAD` ?? "Unknown version");
    else
        $ver = "Build 2";

    define("VOB_VERSION", "Quadrange ($ver)", false);
    define("VOB_DEFAULT_PER_PAGE", 10, false);
    define("__VOB_ERROR_CLOCK_IN_FUTURE", "Server clock error: FK1200-DTF", false);
});
