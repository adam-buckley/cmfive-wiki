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
	 * Check if word is a wiki word
	 * ie an amalgamation of two or more words composed of at least two 
	 * letters each, without intervening white spaces, where the first 
	 * letter of each component word is capitalized and the remaining 
	 * letters are in lowercase.
	 *********************************************************/
	function isWikiWord($word) {
		$i=substr($word,0,1);
		// basic check if first letter is upper case, string has no spaces and string is all alphabetic charactrs
		//print_r([$word,strtoupper($i) === $i ,strpos($word,' ')===false ,ctype_alpha($word) === true]);
		if (strtoupper($i) === $i && strpos($word,' ')===false &&  ctype_alpha($word) === true) {
			// split word into subwords split by upper case letter
			$words=[];
			$wordPos=0;
			foreach (str_split($word) as $letter) {
				if (strtoupper($letter) === $letter) {
					$wordPos++;
				} 
				if (!array_key_exists($wordPos,$words)) $words[$wordPos]='';
				$words[$wordPos].=$letter;
			}
			//print_r($words);
			// are there at least two words with at least two letters per word
			$result=true;
			foreach($words as $k => $w) {
				$result = ($result && strlen($words[$k])>1);
			}
			if (count($words) > 1 && $result)  {
				return $result;
			}
		} 
		return false;
	}
	/*********************************************************
	 * Replace wiki page links
	 *********************************************************/
	function replaceWikiPageLinks($body) {
		$urlParts=explode('/',$this->printSearchUrl());
		$p1=explode('[[',$body);
		$final=[];
		foreach ($p1 as $k=>$token) {
			$tokenParts=explode(']]',$token);
			$title=$tokenParts[0];
			// if content of brackets is a wiki word, link it
			if ($this->isWikiWord($title)) {
				$link="";
				if ($this->getWiki()->type=="richtext") {
					$link="<a class='wikiwordlink wikiwordlink-".$title."' href='".WEBROOT .'/'. implode("/",array_slice($urlParts,0,count($urlParts)-1))."/".$title."' >".$title."</a>";
				} else if ($this->getWiki()->type=="markdown") {
					$link="[".$title."](".WEBROOT .'/'. implode("/",array_slice($urlParts,0,count($urlParts)-1))."/".$title.")";
				} else {
					$link=$title;
				}
				$tokenParts=array_slice($tokenParts,1);
				$tokenParts[0]=$link.$tokenParts[0];
				// lose brackets around link on implode
				// if not the first entry, append to previous entry and slice out current
				if ($k>0) {
					$p1[$k-1]=$p1[$k-1].implode("]]",$tokenParts);
					$p1=array_splice($p1,$k-1,1);
				} else {
					$p1[$k]=implode("]]",$tokenParts);
				} 
			} else {
				$p1[$k]=implode("]]",$tokenParts);
			}
		}
		return implode('[[',$p1);
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
				$this->body=$this->replaceWikiPageLinks($this->body);
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
	
	/* 
	* 
	* 
	* 
	* 

FROM UPDATE()
			// replace wiki links
			//$this->body = preg_replace("/\[\[([a-zA-Z0-9]+)\]\]/", "<a href='" . WEBROOT . "/wiki/view/" . $wn . "/\\1'>\\1</a>", $this->body);
			
			if ($wiki->type=='richtext') {
				$bodyText=strip_tags($this->body);
				$bodyParts=explode(' ',$bodyText);
				$urlParts=explode('/',$this->printSearchUrl());
				$wikiWords=[];
				// for html, convert to text to find wiki words
				foreach ($bodyParts as $k => $token) {
					if ($this->isWikiWord($token)) {
						$wikiWords[]=$token;
					}
				}
				print_r($wikiWords);
				die();
				// replace wiki words in bodytext with link
				foreach ($wikiWords as $word) {
					$this->body=str_replace(
						$word,
						"<a href='".WEBROOT . implode("/",array_slice($urlParts,0,count($urlParts)-1))."/".$word."' >".$word."</a>",
						$this->body
					);
				}
			} else if ($wiki->type=='markdown') {
				$bodyParts=explode(' ',$this->body);
				$urlParts=explode('/',$this->printSearchUrl());
				foreach ($bodyParts as $k => $token) {
					if ($this->isWikiWord($token)) {
						$bodyParts[$k]="<a href='".WEBROOT .implode("/",array_slice($urlParts,0,count($urlParts)-1))."/".$token."' >".$token."</a>";
					}
				}
				$this->body=implode(' ',$bodyParts);
			}
		//	echo $this->body;
			//die();
		
 
	* 
	* 
	* */
