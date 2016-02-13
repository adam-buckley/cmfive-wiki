<?php
/*****************************
 * Persistence tools for Wiki
 *****************************/
class WikiService extends DbService {
	/*****************************
	 * Retrieve a wiki by id
	 * @return Wiki or null
	 * @throws WikiNoAccessException
	*****************************/
	function getWikiById($wiki_id) {
		$wiki = $this->getObject("Wiki",$wiki_id);
		if ($wiki && $wiki->canRead($this->w->Auth->user())) {
			return $wiki;
		} else {
			throw new WikiNoAccessException("You have no access to this wiki.");
		}
	}

	/*****************************
	 * Retrieve a wiki by it's name
	 * @return Wiki or null
	 * @throws WikiNoAccessException
	*****************************/
	function getWikiByName($name) {
		$wiki = $this->getObject("Wiki",array("name"=>$name));
		if (!$wiki) return null;
		
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
	function getWikis() {
		// admin is allowed access to all records
		if ($this->w->Auth->user()->is_admin) {
			return $this->getObjects("Wiki",array("is_deleted" => 0));
		} else {
			// get a list of wiki/user associations for the current logged in user 
			$wus = $this->getObjects("WikiUser", array("user_id" => $this->w->Auth->user()->id));
			if (!$wus) {
				return null;
			}
			// load wikis this user is associated with
			foreach ($wus as $wu) {
				$wikis[$wu->wiki_id] = $this->getObject("Wiki",$wu->wiki_id);
			} 
			// also load public wikis 
			$public=$this->getObjects("Wiki",['is_public'=>true]);
			foreach ($public as $publicWiki) {
				$wikis[$publicWiki->id]=$publicWiki;
			}
			return array_values($wikis);
		}
	}
	
	/*****************************
	 * Create a new wiki record in the database
	 * @return Wiki or null
	*****************************/
	function createWiki($title, $is_public,$type='markdown') {
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
    public function navigation(Web $w, Wiki $wiki = null, $page = null) {
        if (!empty($wiki)){
            if (property_exists($wiki, "title")) {
                $w->ctx("title", $wiki->title . (!empty($page) ? " - " . $page : ''));
            }
        }
          
        $nav = !empty($nav) ? $nav : array();
        if ($w->Auth->loggedIn()) {
            $w->menuLink("wiki/index", "Wiki List", $nav);
            $w->menuLink("wiki/createwiki", "New Wiki", $nav);
        }
        $w->ctx("navigation", $nav);
        return $nav;
    }
}
