<?php declare(strict_types=1);
namespace vob\Web\Presenters;
use Nette\InvalidStateException as ISE;
use Nette\Utils\Image;
use vob\Web\Models\Entities\Utils\Media;

final class BlobPresenter extends VOBPresenter
{
    private function getDirName($dir): string
    {
        if(gettype($dir) === "integer") {
            $dir = (string) $dir;
            if(strlen($dir) < 2) #Must have been a number with 1 digit
                $dir = "0$dir";
        }
        
        return $dir;
    }

    protected function pathFromHash(string $hash): string
    {
        $dir = (new Media)->getBaseDir() . substr($hash, 0, 2);
        if(!is_dir($dir))
            mkdir($dir);
        
        return "$dir/$hash.jpg";
    }
    
    function renderFile(/*string*/ $dir, string $name, string $format)
    {
        $dir  = $this->getDirName($dir);
        $name = preg_replace("%[^a-zA-Z0-9_\-]++%", "", $name);
        $path = VOB_ROOT . "/storage/$dir/$name.$format";
        if(!file_exists($path)) {
            $this->notFound();
        } else {
            if(isset($_SERVER["HTTP_IF_NONE_MATCH"]))
                exit(header("HTTP/1.1 304 Not Modified"));
            
            header("Content-Type: " . mime_content_type($path));
            header("Content-Size: " . filesize($path));
            header("ETag: W/\"" . hash_file("snefru", $path) . "\"");
            
            readfile($path);
            exit;
        }
    }

    function renderUploadFile()
    {
        $this->assertUserLoggedIn();
    
        if($this->user->identity->mayWriteAccessToArticles()) {
            if($_SERVER["REQUEST_METHOD"] === "POST") {
                if(!isset($_FILES["blob"]))
                    throw new ISE("Не выбран файл");

                if($_FILES["blob"]["error"] == 1)
                    throw new ISE("Файл повреждён");

                bdump($_FILES["blob"]);

                $hash = hash_file("whirlpool", $_FILES["blob"]['tmp_name']);
                
                $image = new \Imagick;
                $image->readImage($_FILES["blob"]['tmp_name']);
                $h = $image->getImageHeight();
                $w = $image->getImageWidth();
                if(($h >= ($w * 7)) || ($w >= ($h * 7)))
                    throw new ISE("Invalid layout: image is too wide/short");
                $sizes = Image::calculateSize(
                    $image->getImageWidth(), $image->getImageHeight(), 8192, 4320, Image::SHRINK_ONLY | Image::FIT
                );

                $image->resizeImage($sizes[0], $sizes[1], \Imagick::FILTER_HERMITE, 1);
                $image->writeImage($this->pathFromHash($hash));
                exit((new Media)->getURL($hash, "jpg"));
            }
        } else {
            $this->flashFail("error", "You don't have access to edit or create articles");
        }
    }
}