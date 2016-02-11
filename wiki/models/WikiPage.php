<?php
class WikiPage extends DbObject {
	
	public $_searchable;
	public $_exclude_index = array("is_deleted");
	
	public $name;
	public $wiki_id;
	public $dt_created;
	public $dt_modified;
	public $creator_id;
	public $modifier_id;
	public $is_deleted;
	public $body;
	
	
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
		return $this->getObjects("WikiPageHistory",array("wiki_page_id"=>$this->id));
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
		// only update if the body has changed
		$oldRecord=$this->getObject('WikiPage',$this->id,false);
		if (trim($oldRecord->body) != trim($this->body)) {
			$h= $this->getRecentHistory(1);
			parent::update();
			// protect against ajax history spamming by diff page vs history save dates
			// if there are history entries
			if (count($h)>0)  {
				// if gap between page save right now and last history is less that 1 minute dont add a history entry
				$historyUpdated=$h[0]->dt_modified;
				if ((($this->dt_modified - $historyUpdated) > 60)) {
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
			
		}
	}

	function insert($force_validation = false) {
		parent::insert();
		$hist = new WikiPageHistory($this->w);
		$hist->fill($this->toArray());
		$hist->id = null;
		$hist->wiki_page_id = $this->id;
		$hist->insert();
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
