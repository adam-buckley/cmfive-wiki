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
	
	function update($force_null_values = false, $force_validation = false) {
		// protect against ajax history spamming
		$h= $this->getWiki()->getRecentHistory(2);
		// diff first two histories (as stable timezones)
		if (count($h)>1)  {
			$ts=$h[1]['dt_created'];
			$now=$h[0]['dt_created'];
			// if gap between last two histories is less that 2 minutes
			if ((($now - $ts) > 120)) {
				$hist = new WikiPageHistory($this->w);
				$hist->fill($this->toArray());
				$hist->id = null;
				$hist->wiki_page_id = $this->id;
				$hist->insert();
			}
		// need to create first two entries
		} else {
			$hist = new WikiPageHistory($this->w);
			$hist->fill($this->toArray());
			$hist->id = null;
			$hist->wiki_page_id = $this->id;
			$hist->insert();
		}
		parent::update();
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
