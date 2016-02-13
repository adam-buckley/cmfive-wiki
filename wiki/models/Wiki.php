<?php
/*****************************
 * Persistent object representing a Wiki
*****************************/
class Wiki extends DbObject{
	public $title;
	public $name;
	public $type;
	public $dt_created;
	public $creator_id;
	public $dt_modified;
	public $modifier_id;

	public $is_deleted;
	public $owner_id;
	public $is_public;
	public $last_modified_page_id;

	/*****************************
	 * Load history entries for this wiki
	 * @return array 
	*****************************/
	function getHistory() {
		$sql="
		SELECT DISTINCT name, creator_id, unix_timestamp(dt_created) as dt_created,id
		FROM wiki_page_history
		WHERE wiki_id = ".$this->id." order by dt_created desc, name asc";
		
		return $this->_db->sql($sql)->fetch_all();
	}
	
	/*****************************
	 * Load all page for this wiki
	 * @return WikiPage or null 
	*****************************/
	function getPages() {
		return $this->getObjects("WikiPage",
			array("is_deleted"=>0,
				  "wiki_id"=>$this->id
			)
		);
	}
	
	/*****************************
	 * Load the wiki page named HomePage for this wiki
	 * @return WikiPage or null
	*****************************/
	function getHomePage() {
		return $this->getPage($this->id,"HomePage");
	}

	/*****************************
	 * Load page with matching name for this wiki
	 * @return WikiPage or null 
	*****************************/
	function getPage($name) {
		return $this->getObject("WikiPage",
		array("is_deleted"=>0,
				  "wiki_id"=>$this->id,
				  "name"=>$name));
	}
	
	/*****************************
	 * Load a wiki page with matching id
	 * @return WikiPage or null 
	*****************************/	
	function getPageById($id) {
		return $this->getObject("WikiPage",
		array("is_deleted"=>0,
				  "id"=>$id));
	}
	
	/*****************************
	 * Generate the name of the wiki from the title by removing spaces
	 * @return string
	*****************************/
	function getName() {
		return ucfirst(str_replace(" ","",$this->title));
	}

	/*****************************
	 * Insert a new wiki record into the database
	 * Validate and set automatic fields for create
	 * @return 
	 * @throws WikiException
	 * @throws WikiExistsException
	*****************************/	
	function insert($force_validation = false) {
		if (!$this->title) {
			throw new WikiException("This wiki needs a title.");
		}
		$this->name = $this->getName();
		$this->owner_id = $this->w->Auth->user()->id;

		// check if wiki of the same name exists!
		$ow = $this->Wiki->getWikiByName($this->getName());
		if ($ow) {
			throw new WikiExistsException("Wiki of name ".$this->getName()." already exists.");
		}
		parent::insert();
		if ($this->type=="richtext" ) {
			$this->addPage("HomePage", "<h1>This is the HomePage</h1>");
		} else {
			$this->addPage("HomePage", "#This is the HomePage");
		}
		$this->addUser($this->w->Auth->user(),"editor");
	}

	/*****************************
	 * Create a new wiki page in the database for this wiki
	 * @return array 
	*****************************/
	function addPage($name,$body) {
		$p = new WikiPage($this->w);
		$p->wiki_id = $this->id;
		$p->name = $name;
		$p->body = $body;
		$p->insert();
		$this->last_modified_page_id = $p->id;
		$this->update();
		return $p;
	}

	/*****************************
	 * Load all wiki user associations for this wikie
	 * @return array 
	*****************************/
	function getUsers() {
		return $this->getObjects("WikiUser",array("wiki_id"=>$this->id));
	}


	/*****************************
	 * PER RECORD ACCESS CONTROLS
	*****************************/
	/*****************************
	 * Check if a user can read this record
	 * @return boolean 
	*****************************/
	function canRead(User $user) {
		$wu = $this->getObject("WikiUser",array("user_id"=>$user->id,"wiki_id"=>$this->id));
		$ret=(	
			$this->Auth->user()->is_admin || 
			$this->is_public ||
			(	$wu != null && 
				($this->isOwner($user) || $wu->role == "reader" || $wu->role == "editor")
			)
		);
		return $ret;
	}

	/*****************************
	 * Check if a user can edit this record
	 * @return boolean 
	*****************************/	
	function canEdit(User $user) {
		$wu = $this->getObject("WikiUser",array("user_id"=>$user->id,"wiki_id"=>$this->id));
		return $this->Auth->user()->is_admin ||($wu != null && ($this->isOwner($user)  || $wu->role == "editor"));
	}

	/*****************************
	 * Check if a user can delete this record
	 * @return boolean 
	*****************************/	
	function canDelete(User $user) {
		if ($this->isOwner($user) || $this->Auth->user()->is_admin) {
			return true;
		} else {
			return false;
		}
	}

	/*****************************
	 * Check if this user is a member of any wikis
	 * @return boolean
	*****************************/	
	function isUser($user) {
		$wu = $this->getObject("WikiUser",array("user_id"=>$user->id,"wiki_id"=>$this->id));
		return $wu != null;	
	}

	/*****************************
	 * Store a new user for this wiki
	 * You may provide an addition role parameter.default is reader
	 * @param User
	 * @param string
	 * @return  
	*****************************/	
	function addUser($user,$role="reader") {
		if (!$this->isUser($user)) {
			$wu = new WikiUser($this->w);
			$wu->wiki_id = $this->id;
			$wu->user_id = $user->id;
			$wu->role = $role;
			$wu->insert();
		}
	}

	/*****************************
	 * Get a WikiUser object by WikiUser::id
	 * @param int $id
	 ******************************/
	function getUserById($id) {
		return $this->getObject("WikiUser",$id);
	}
	
	/*****************************
	* Remove a wiki user by the wiki_user::id
	******************************/
	function removeUser($id) {
		$wu = $this->getUserById($id);
		if ($wu) {
			$wu->delete();
		}
	}
	
	/*****************************
	 * Check if a user is the owner of this wiki
	 * @return boolean 
	*****************************/
	function isOwner($user) {
		return $this->owner_id == $user->id;
	}
	
	/*****************************
	 * Delete a wiki and all associated records
	 * @return 
	*****************************/
	function delete($force = false) {
		$pages=$this->getPages();
		if (!empty($pages))  {
			foreach ($pages as $page) {
				if (!empty($page))$page->delete($force);
			}
		}
		$users=$this->getUsers();
		if (!empty($users))  {
			foreach ($users as $user) {
				if (!empty($user)) $user->delete($force);
			}
		}
		parent::delete($force);
	}
	
	  function printSearchTitle() {
        return $this->title;
    }

    function printSearchListing() {
       return '';
    }

    function printSearchUrl() {
        return "wiki/view/" . $this->name;
    }
}
