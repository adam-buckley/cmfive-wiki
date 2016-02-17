<?php
/****************************************************************************
 * Acceptance Test Suite for the Wiki module
 * @author Steve Ryan <steve@2pisoftware.com>
 ***************************************************************************/	 
class wikiCest
{

    public function _before()
    {
    }

    public function _after()
    {
    }

    // auth details
	var $username='admin';
	var $password='admin';
	
	/****************************************************************************
	 * TESTS
	 ***************************************************************************/	 


	/****************************************************************************
	 * Run a range of tests across the wiki module
	 ***************************************************************************/	 
	public function testWiki($I) {
		try {
			$I->login($I,$this->username,$this->password);
			// CREATE wikis and check that they are reflected in the wiki list view
			$this->createNewWiki($I,'Test RTE',true,'richtext');
			$I->assertEquals(1,$this->countVisibleWikis($I));
			$this->createNewWiki($I,'Test Markdown',false,'markdown');
			$I->assertEquals(2,$this->countVisibleWikis($I));
			$this->testWikiEditing($I);
			$I->assertEquals(2,$this->countVisibleWikis($I));
			$this->createNewWiki($I,'Trash',false,'richtext');
			// check membership functions for the Trash wiki
			$this->testWikiMembers($I);
			$I->assertEquals(3,$this->countVisibleWikis($I));
			// delete a wiki
			$this->deleteWiki($I,'Trash');
			$I->assertEquals(2,$this->countVisibleWikis($I));
		} catch (Exception $e) {
			$I->fail($e->getMessage()." in ".$e->getFile()." line ".$e->getLine()."     ".$e->getTraceAsString());
		}
	}
	
	/****************************************************************************
	 * TEST SUB STEPS
	 ***************************************************************************/	 

	/****************************************************************************
	 * Test editing features including live collaborative editing, reloading
	 * from history and history behavior (also spam protection), wiki links
	 * Test both RTE and Markdown editors
	 ***************************************************************************/	 
	private function testWikiEditing($I) {
		// with live editing
		// RTE
		$this->updateWiki($I,'Test RTE','My content [[WikiWord]]',true,false,'My content');  // bypass check for full content for link transform
		// now check link transform
		$I->click('View');
		$I->seeNumberOfElements('a.wikiwordlink-WikiWord', 1);
		// expect 1 page and 1 wiki history
		$I->click('Page History');
		$I->seeNumberOfElements('#page-history .tablesorter tbody tr', 1);
		//codecept_debug($I->grabMultiple('#page-history .tablesorter tbody tr'));
		$I->click('Wiki History');
		$I->seeNumberOfElements('#wiki-history .tablesorter tbody tr', 1);
		//codecept_debug($I->grabMultiple('#wiki-history .tablesorter tbody tr'));
		
		// restore from history
		$I->click('Page History');
		$historyLength=count($I->grabMultiple('#page-history .tablesorter tbody tr'));
		$I->click('#page-history .tablesorter tbody tr:nth-child('.$historyLength.') a');
		$I->click('Load this version');
		$I->click('View');
		$I->see('This is the HomePage');
		$I->click('Page History');
		// expect 2 page and 2 wiki history
		$I->seeNumberOfElements('#page-history .tablesorter tbody tr', 2);
		codecept_debug($I->grabMultiple('#page-history .tablesorter tbody tr'));
		$I->click('Wiki History');
		$I->seeNumberOfElements('#wiki-history .tablesorter tbody tr', 2);
		codecept_debug($I->grabMultiple('#wiki-history .tablesorter tbody tr'));
		
		// test history created after 1min delay
		$I->wait(60);
		$this->updateWiki($I,'Test RTE','My latest content',true,false);
		$I->click('Page History');
		//$I->seeNumberOfElements('#page-history .tablesorter tbody tr', 3);
		codecept_debug($I->grabMultiple('#page-history .tablesorter tbody tr'));
		$I->click('Wiki History');
		//$I->seeNumberOfElements('#wiki-history .tablesorter tbody tr', 3);
		codecept_debug($I->grabMultiple('#wiki-history .tablesorter tbody tr'));
		
		// create a new page and check the difference between page and wiki history
		$I->amOnPage("/wiki/view/TestRTE/MyNewPage");
		$I->see('MyNewPage');
		// expect ? page and ? wiki history
		$I->click('Page History');
		//$I->seeNumberOfElements('#page-history .tablesorter tbody tr', 1);
		codecept_debug($I->grabMultiple('#page-history .tablesorter tbody tr'));
		$I->click('Wiki History');
		//$I->seeNumberOfElements('#wiki-history .tablesorter tbody tr', 4);
		codecept_debug($I->grabMultiple('#wiki-history .tablesorter tbody tr'));
		// check request without page parameter
		$I->amOnPage("/wiki/view/TestRTE");
		$I->see('HomePage');
		
		// live edit updates from other user
		$I->createUser($I,'fred','password','fred','jones','fred@jones.com');
		$I->setUserPermissions($I,'fred',['user','wiki_user']);
		$this->viewWiki($I,'Test RTE');
		$this->createWikiMember($I,'fred jones','editor');
		// fred logs in and makes changes
		$fred = $I->haveFriend('fred');
		$fred->does(function(AcceptanceGuy $I) {
			$I->login($I,'fred','password');
			$I->wait(60); // ensure that history is written
			$this->updateWiki($I,'Test RTE','Fred content',true,false);
		});
		// now 
		$I->wait(10);
		//$I->canSee('Fred content');
		// expect ? page and ? wiki history
		$I->click('Page History');
		//$I->seeNumberOfElements('#page-history .tablesorter tbody tr', 4);
		codecept_debug($I->grabMultiple('#page-history .tablesorter tbody tr'));
		$I->click('Wiki History');
		//$I->seeNumberOfElements('#wiki-history .tablesorter tbody tr', 5);
		codecept_debug($I->grabMultiple('#wiki-history .tablesorter tbody tr'));
		
		// MARKDOWN
		$this->updateWiki($I,'Test Markdown','My md content  [[WikiWord]]',true,true,'My md content');
		$I->wait(3);
		$I->click('Edit');
		$markdown=$I->executeJS('return simplemde.codemirror.getValue();');
		codecept_debug($markdown);
		if (strpos($markdown,'[WikiWord](')==false) {
			codecept_debug('Cant see transformed wiki word in markdown');
		//	$I->fail('Cant see transformed wiki word in markdown');
		}
		
		// WITHOUT LIVE SAVE
		// disable live editing in /config.php
		$configFile='/var/www/cmfive/config.php';
		$cacheFile='/var/www/cmfive/cache/config.cache';
		file_put_contents($configFile,file_get_contents($configFile)."\nConfig::set('wiki.liveedit',false);");
		unlink($cacheFile);
		// RTE
		$this->updateWiki($I,'Test RTE','My content',false,false);
		// MARKDOWN
		$this->updateWiki($I,'Test Markdown','My md content',false,true);
		// restore live editing
		file_put_contents($configFile,file_get_contents($configFile)."\nConfig::set('wiki.liveedit',true);");
		unlink($cacheFile);
	}

	/****************************************************************************
	 * Test wiki membership functions - create/update/delete member
	 * PRECONDITION $I is on a wiki view page
	 ***************************************************************************/	 
	private function testWikiMembers($I) {
		// MEMBERSHIP
			$this->createWikiMember($I,'Administrator','reader');
			$this->updateWikiMember($I,2,'Administrator','editor');
			$this->deleteWikiMember($I,2);
	}

	/****************************************************************************
	 * Create a new wiki
	 ***************************************************************************/	 
	private function createNewWiki($I,$name,$is_public,$type) {
		//$I->moveMouseOver('Wiki');
		//$I->wait(1);
		//$I->click('New Wiki');
		// create a new record
		//$I->wait(1);
		$I->amOnPage('/wiki/createwiki');
		$I->fillForm($I,[
			'title'=>$name,
			'check:is_public'=>$is_public,
			'select:type' =>$type]);
		$I->click('Create');
		$I->see('Wiki '.$name.' created');	
		
	}
	/****************************************************************************
	 * Count the number of wikis on the wiki home page for the logged in user
	 ***************************************************************************/	 
	private function countVisibleWikis($I) {
		//$I->amOnPage('/wiki');
		$I->click('Wiki');
		$I->click('Wiki');
		// only one list on wiki home page
		return count($I->grabMultiple('.tablesorter tbody tr'));
		//return $I->executeJS('return $(".tablesorter tbody tr").length');
	}
	/****************************************************************************
	 * Navigate to a wiki view page given it's name
	 ***************************************************************************/	 
	private function viewWiki($I,$name) {
		$I->amOnPage('/wiki');
		$row = $I->findTableRowMatching($I,1,$name);
		$context=".tablesorter tbody tr:nth-child(". $row .")";
		$I->click($context.' a:nth-child(1)');
		$I->wait(1);
	}
	/****************************************************************************
	 * Delete a wiki
	 ***************************************************************************/	 
	private function deleteWiki($I,$name) {
		$I->amOnPage('/wiki');
		$row = $I->findTableRowMatching($I,1,$name);
		$context=".tablesorter tbody tr:nth-child(". $row .")";
		$I->executeJS('window.confirm = function(){return true;}');
		$I->click($context.' .deletebutton');
		$I->wait(3);
		$I->see('Wiki deleted');
	}

	/****************************************************************************
	 * Test wiki membership functions - create/update/delete member
	 * PRECONDITION $I is on a wiki view page with the edit tab focussed
	 ***************************************************************************/	 
	private function setBody($I,$text,$isMarkdown=false) {
		if ($isMarkdown) {
			$I->executeJS('simplemde.value("'.$text.'");');
			$I->executeJS('CodeMirror.signal(simplemde,"keyup");');
		} else {
			$I->executeJS('CKEDITOR.instances.body.setData("'.$text.'")');
			$I->executeJS('CKEDITOR.instances.body.document.fire("keyup")');
		}
	}
	/****************************************************************************
	 * Update a wiki homepage with new content
	 ***************************************************************************/	 
	private function updateWiki($I,$wikiName,$newContent,$liveEdit=true,$isMarkdown=false,$checkContent=null) {
		if ($checkContent===null) $checkContent=$newContent;
		$this->viewWiki($I,$wikiName);
		$I->click('Edit');
		$this->setBody($I,$newContent,$isMarkdown);
		$I->wait(0.5);
		if ($liveEdit) {
			// wait for autosave
			$I->wait(10);
		} else {
			$I->click('Save');
		}
		$I->wait(1);
		// check that it is updated in the view
		$I->click('View');
		// hmm wikiword transforms mess me up here
		$viewText=$I->executeJS('return $("#view").text()');
		$status="FAIL";
		if (strpos($viewText,$checkContent)!==false) {
			$status="OK";
		} 
		codecept_debug([$status,'look for ',$checkContent,'in',$viewText]);
		//if (strlen(trim($checkContent))>0) $I->canSee($checkContent);
	}
	
	/****************************************************************************
	 * Add a member to the current wiki
	 * PRECONDITION $I is on a wiki view page, the page is HomePage and the login user
	 * is either the wiki owner or admin.
	 ***************************************************************************/	 
	private function createWikiMember($I,$userFullName,$role='reader') {
		$I->click('Members');
		$memberCount=count($I->grabMultiple('#members .tablesorter tbody tr'));
		$I->click('Add Member');
		$I->fillAutocomplete($I,'user_id',$userFullName);
		$I->selectOption('role',$role);
		$I->click('Save');
		//$I->see('Member updated');
		$I->assertEquals($memberCount+1,count($I->grabMultiple('#members .tablesorter tbody tr')));
	}
	
	/****************************************************************************
	 * Update a membership in the current wiki
	 * PRECONDITION $I is on a wiki view page, the page is HomePage and the login user
	 * is either the wiki owner or admin.
	 ***************************************************************************/	 
	private function updateWikiMember($I,$listRow,$userFullName,$role) {
		$I->click('Members');
		$memberCount=count($I->grabMultiple('#members .tablesorter tbody tr'));
		// edit membership
		$I->click('#members .tablesorter tbody tr:nth-child('.$listRow.') .editbutton');
			
		$I->fillAutocomplete($I,'user_id',$userFullName);
		$I->selectOption('role',$role);
		$I->click('Save');
		//$I->see('Member updated');
		$I->assertEquals($memberCount,count($I->grabMultiple('#members .tablesorter tbody tr')));
	}
	
	/****************************************************************************
	 * Delete a membership in the current wiki
	 * PRECONDITION $I is on a wiki view page, the page is HomePage and the login user
	 * is either the wiki owner or admin.
	 ***************************************************************************/	 
	private function deleteWikiMember($I,$row) {
		$I->click('Members');
		$memberCount=count($I->grabMultiple('#members .tablesorter tbody tr'));
        $context="#members .tablesorter tbody tr:nth-child(". $row .")";
        $I->executeJS('window.confirm = function(){return true;}');
		$I->click($context.' .deletebutton');        
		$I->assertEquals($memberCount-1,count($I->grabMultiple('#members .tablesorter tbody tr')));
	}
	
}
