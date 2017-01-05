<?php
class WikiPage extends DbObject {
	
	public $_searchable;
	public $_exclude_index = array("is_deleted","children");
	
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
	
	function getChildren($force_update = false) {
		if ($force_update) {
			preg_match_all("\[\[page((?:\|.*?)*)\]\]",$this->body,$matches);
			if (empty($matches)) {
				$this->children = "";
			} else {
				$children = [];
				foreach ($matches as $match) {
					if (isset($match[1])) {
						$children[]=$match[1]; // save only the page name
					}
				}
				$this->children = implode(",", $children);
			}
		}
		return $this->children;
	}
	
	function getWiki() {
		return $this->Wiki->getWikiById($this->wiki_id);			
	}
	
	function canList(User $user) {
		try {
			$wiki = $this->getWiki();
			return $wiki->canRead($user);
		} catch (WikiException $ex) {
			return false;
		}
	}
	
	function getHistory() {
		return $this->getObjects("WikiPageHistory",array("wiki_page_id"=>$this->id),false,true,'dt_created desc,name asc',null);
	}
	
	/*****************************
	 * Load history entries for this wiki with results limited
	 * @return array 
	*****************************/
	function getRecentHistory($limit=1) {
		return $this->getObjects("WikiPageHistory",array("wiki_id"=>$this->wiki_id,"wiki_page_id"=>$this->id),false,true,'dt_created desc,name asc',null,$limit);
	}
	
	
	function canView(User $user) {
		return $this->canList($user);
	}
	
	function printSearchListing() {
		$txt = "Last Modified: ";
		$txt .= formatDateTime($this->dt_modified);
		$txt .= " by ".$this->Auth->getUser($this->modifier_id)->getFullName();
		return $txt;				
	}
	
	function printSearchTitle() {
		return $this->getWiki()->title.", ".$this->name;
	}
	
	function printSearchUrl() {
		return "wiki/view/".$this->getWiki()->name."/".$this->name;
	}

	/*********************************************************
	 * Update a wiki page and create wiki page history entries
	 *********************************************************/
	function update($force_null_values = false, $force_validation = false) {
		$wiki=$this->getWiki();
		// only update if the body has changed
		$response=false;
		$oldRecord=$this->getObjects('WikiPage',['id'=>$this->id]);
		if (is_array($oldRecord) && count($oldRecord)>0) {
			$oldRecord=$oldRecord[0];
		}
		if (!empty($oldRecord)) {
			if (trim($oldRecord->body) != trim($this->body)) {
				$this->body = WikiLib::replaceWikiMacros($wiki,$this,$this->body);
				$this->getChildren(true);
				// protect against ajax history spamming by diff page vs history save dates
				$h= $this->getRecentHistory(1);
				$response=parent::update();
				// if there are history entries
				if (count($h)>0)  {
					// if gap between page save right now and last history is less that 1 minute dont add a history entry
					$historyUpdated=$h[0]->dt_modified;
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
				$wiki->last_modified_page_id=$this->id;
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
	function insert($force_validation = false) {
		$this->body = WikiLib::replaceWikiMacros($this->getWiki(),$this,$this->body);
		$this->getChildren(true);
		$a=parent::insert();
		$hist = new WikiPageHistory($this->w);
		$hist->fill($this->toArray());
		$hist->id = null;
		$hist->wiki_page_id = $this->id;
		$hist->insert();
		// update wiki $last_modified_page_id (replace wiki->updatePage)
		$wiki=$this->getWiki();
		$wiki->last_modified_page_id=$this->id;
		$wiki->update();
		return $a;
	}

	function getDbTableName() {
		return "wiki_page";
	}

	function getHtml() {
		return $this->body;
	}
	
	/*****************************
	 * Delete a wiki page and all associated history
	 * @return 
	*****************************/
	function delete($force = false) {
		$history=$this->getHistory();
		if (!empty($history))  {
			foreach ($history as $h) {
				if (!empty($h)) $h->delete($force);
			}
		}
		parent::delete($force);
	}


}
