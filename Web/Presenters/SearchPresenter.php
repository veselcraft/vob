<?php declare(strict_types=1);
namespace vob\Web\Presenters;
use vob\Web\Models\Entities\Article;
use vob\Web\Models\Repositories\Articles;
use Chandler\Database\DatabaseConnection;

final class SearchPresenter extends VOBPresenter
{
    private $articles;

    function __construct(Articles $articles)
    {
        $this->articles = $articles;
        
        parent::__construct();
    }

    function renderIndex(): void
    {
        $query = $this->queryParam("query") ?? "";
        $page  = (int) ($this->queryParam("p") ?? 1);
        
        $results  = $this->articles->find($query);
        $iterator = $results->page($page);
        $count    = $results->size();
        
        $this->template->query = $query;
        $this->template->iterator = iterator_to_array($iterator);
        $this->template->count    = $count;
        $this->template->type     = $type;
        $this->template->page     = $page;
    }
}