<?php

class WikiPage extends DbObject
{
    public $_searchable;
    public $_exclude_index = ["is_deleted", "children"];

    public $name;
    public $wiki_id;
    public $dt_created;
    public $dt_modified;
    public $creator_id;
    public $modifier_id;
    public $is_deleted;
    public $body;

    /**
     * this is a comma separated list of wiki page names which are linked to from this wiki
     * these pages are recognised only by the use of the [[page|name|title]] shortcode!
     */
    public $children = "";

    /**
     * search the body for page shortcodes and store the page names
     * in the children list.
     *
     * @param string $force_update
     * @return string
     */
    private function updateChildren()
    {
        $matches = [];
        preg_match_all("/\[\[page((?:\|.*?)*)\]\]/", $this->body, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            $this->children = "";
        } else {
            $children = [];
            foreach ($matches as $match) {
                if (empty($match)) {
                    continue;
                }

                if (isset($match[1])) {
                    $options = explode("|", $match[1]);
                    array_shift($options);
                    $children[] = $options[0]; // save only the page name
                }
            }
            $this->children = "|" . implode("|", array_unique($children)) . "|";
        }
    }

    /**
     * find all wiki pages which list this page's name in
     * their children list.
     *
     * @return array of WikiPage objects
     */
    public function getParents()
    {
        return $this->getObjects("WikiPage", ["is_deleted" => 0, "wiki_id" => $this->wiki_id, "children LIKE ?" => "%|{$this->name}|%"]);
    }

    /**
     * find all wiki pages which are linked to from this page
     *
     * @return array of WikiPage objects
     */
    public function getChildren()
    {
        $children = [];
        $names = explode(substr($this->children, 1, -1));
        if (!empty($names)) {
            foreach ($names as $name) {
                $page = $this->getObject("WikiPage", ["is_deleted" => 0, "name" => $name]);
                if (!empty($page)) {
                    $children[] = $page;
                }
            }
        }
        return $children;
    }

    public function getWiki()
    {
        return $this->Wiki->getWikiById($this->wiki_id);
    }

    public function canList(User $user)
    {
        try {
            $wiki = $this->getWiki();
            return $wiki->canRead($user);
        } catch (WikiException $ex) {
            return false;
        }
    }

    public function getHistory()
    {
        return $this->getObjects("WikiPageHistory", ["wiki_page_id" => $this->id], false, true, 'dt_created desc,name asc', null);
    }

    /*****************************
     * Load history entries for this wiki with results limited
     * @return array
     *****************************/
    public function getRecentHistory($limit = 1)
    {
        return $this->getObjects("WikiPageHistory", ["wiki_id" => $this->wiki_id, "wiki_page_id" => $this->id], false, true, 'dt_created desc,name asc', null, $limit);
    }


    public function canView(User $user)
    {
        return $this->canList($user);
    }

    public function printSearchListing()
    {
        $txt = "Last Modified: ";
        $txt .= formatDateTime($this->dt_modified);
        $txt .= " by " . AuthService::getInstance($this->w)->getUser($this->modifier_id)->getFullName();
        return $txt;
    }

    public function printSearchTitle()
    {
        return $this->getWiki()->title . ", " . $this->name;
    }

    public function printSearchUrl()
    {
        return "wiki/view/" . $this->getWiki()->name . "/" . $this->name;
    }

    /*********************************************************
     * Update a wiki page and create wiki page history entries
     *********************************************************/
    public function update($force_null_values = false, $force_validation = false)
    {
        $wiki = $this->getWiki();
        // only update if the body has changed
        $response = false;
        $oldRecord = $this->getObjects('WikiPage', ['id' => $this->id]);
        if (is_array($oldRecord) && count($oldRecord) > 0) {
            $oldRecord = $oldRecord[0];
        }
        if (!empty($oldRecord)) {
            if (trim($oldRecord->body) != trim($this->body)) {
                $this->body = WikiLib::replaceWikiMacros($wiki, $this, $this->body);
                $this->updateChildren();
                // protect against ajax history spamming by diff page vs history save dates
                $h = $this->getRecentHistory(1);
                $response = parent::update();
                // if there are history entries
                if (count($h) > 0) {
                    // if gap between page save right now and last history is less that 1 minute dont add a history entry
                    $historyUpdated = $h[0]->dt_modified;
                    if ((($oldRecord->dt_modified - $historyUpdated) > 60)) {
                        $hist = new WikiPageHistory($this->w);
                        $hist->fill($this->toArray());
                        $hist->id = null;
                        $hist->wiki_page_id = $this->id;
                        $hist->insert();
                    }
                    // otherwise need to create first entry
                } else {
                    $hist = new WikiPageHistory($this->w);
                    $hist->fill($this->toArray());
                    $hist->id = null;
                    $hist->wiki_page_id = $this->id;
                    $hist->insert();
                }
                // update wiki $last_modified_page_id (replace wiki->updatePage)
                $wiki->last_modified_page_id = $this->id;
                $wiki->update();
            } else {
                return true;
            }
        }
        return $response;
    }

    /*********************************************************
     * Insert a wiki page and create wiki page history and update wiki
     * last_modified_page
     *********************************************************/
    public function insert($force_validation = false)
    {
        $this->body = WikiLib::replaceWikiMacros($this->getWiki(), $this, $this->body);
        $this->updateChildren();
        $a = parent::insert();
        $hist = new WikiPageHistory($this->w);
        $hist->fill($this->toArray());
        $hist->id = null;
        $hist->wiki_page_id = $this->id;
        $hist->insert();
        // update wiki $last_modified_page_id (replace wiki->updatePage)
        $wiki = $this->getWiki();
        $wiki->last_modified_page_id = $this->id;
        $wiki->update();
        return $a;
    }

    public function getDbTableName()
    {
        return "wiki_page";
    }

    public function getHtml()
    {
        return $this->body;
    }

    /*****************************
     * Delete a wiki page and all associated history
     * @return
     *****************************/
    public function delete($force = false)
    {
        $history = $this->getHistory();
        if (!empty($history)) {
            foreach ($history as $h) {
                if (!empty($h)) {
                    $h->delete($force);
                }
            }
        }
        parent::delete($force);
    }
}
