<?php

/*****************************
 * Persistence tools for Wiki
 *****************************/
class WikiService extends DbService
{
    /*****************************
     * Retrieve a wiki by id
     * @return Wiki or null
     * @throws WikiNoAccessException
     *****************************/
    public function getWikiById($wiki_id)
    {
        $wiki = $this->getObject("Wiki", $wiki_id);
        if ($wiki && $wiki->canRead($this->w->Auth->user())) {
            return $wiki;
        } else {
            throw new WikiNoAccessException("You have no access to this wiki.");
        }
    }

    /*****************************
     * Retrieve a wiki by its name
     * @return Wiki or null
     * @throws WikiNoAccessException
     *****************************/
    public function getWikiByName($name)
    {
        $wiki = $this->getObject("Wiki", ["name" => $name]);

        if (!$wiki) {
            return null;
        }

        if ($wiki->canRead($this->w->Auth->user())) {
            return $wiki;
        } else {
            throw new WikiNoAccessException("You have no access to this wiki.");
        }
    }
    /*****************************
     * Retrieve all wikis that are visible to the logged in user
     * @return array of Wiki or null
     *****************************/
    public function getWikis()
    {
        $wikis = $this->getObjects("Wiki", ["is_deleted" => 0]);
        if (!empty($wikis)) {
            foreach ($wikis as $wiki) {
                if ($wiki->canRead($this->w->Auth->user())) {
                    $ret[] = $wiki;
                }
            }
            return $ret;
        } else {
            return null;
        }
    }

    /*****************************
     * Create a new wiki record in the database
     * @return Wiki or null
     *****************************/
    public function createWiki($title, $is_public, $type = 'markdown')
    {
        $wiki = new Wiki($this->w);
        $wiki->title = $title;
        $wiki->type = $type;
        $wiki->is_public = $is_public ? 1 : 0;
        $wiki->insert();
        return $wiki;
    }

    /*****************************
     * Generate menu entries for navigation
     * @return array
     *****************************/
    public function navigation(Web $w, Wiki $wiki = null, $page = null)
    {
        if (!empty($wiki)) {
            if (property_exists($wiki, "title")) {
                $w->ctx("title", $wiki->title . (!empty($page) ? " - " . $page : ''));
            }
        }

        $nav = !empty($nav) ? $nav : [];

        if ($w->Auth->loggedIn()) {
            $w->menuLink("wiki/index", "Wiki List", $nav);
            $w->menuLink("wiki/createwiki", "New Wiki", $nav);
        }

        $w->ctx("navigation", $nav);
        return $nav;
    }
}
