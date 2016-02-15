<?php
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
	
	
	private function testWikiMembers($I) {
		// MEMBERSHIP
			$this->createWikiMember($I,'Administrator','reader');
			$this->updateWikiMember($I,2,'Administrator','editor');
			$this->deleteWikiMember($I,2);
	}
	
	private function testWikiEditing($I) {
		//$this->testWikiMembers($I);
			
		// with live editing
		// RTE
		$this->updateWiki($I,'Test RTE','My content [[WikiWord]]',true,false,'My content');
		$I->click('View');
		$I->seeNumberOfElements('a.wikiwordlink-WikiWord', 1);
		$I->click('Page History');
		$I->seeNumberOfElements('#page-history .tablesorter tbody tr', 2);
		
		// restore from history
		$I->click('Page History');
		$I->click('#page-history .tablesorter tbody tr:nth-child(1) a');
		$I->click('Load this version');
		
		
			
		// MARKDOWN
		$this->updateWiki($I,'Test Markdown','My md content  [[WikiWord]]',true,true,'');
		$I->wait(3);
		$I->click('Edit');
		$markdown=$I->executeJS('return simplemde.codemirror.getValue();');
		codecept_debug($markdown);
		if (strpos($markdown,'[WikiWord](')==false) {
		//	$I->fail('Cant see transformed wiki word in markdown');
		}
		// disable live editing
		$configFile='/var/www/cmfive/config.php';
		$cacheFile='/var/www/cmfive/cache/config.cache';
		file_put_contents($configFile,file_get_contents($configFile,"\nConfig::set('wiki.liveedit',false)"));
		unlink($cacheFile);
		// RTE
		$this->updateWiki($I,'Test RTE','My content',false,false);
		// MARKDOWN
		$this->updateWiki($I,'Test Markdown','My md content',false,true);
		// restore live editing
		file_put_contents($configFile,file_get_contents($configFile,"\nConfig::set('wiki.liveedit',true)"));
		unlink($cacheFile);
	}
	
	private function testWikiHistory($I) {
		$I->click('Wiki History');
		codecept_debug(count($I->grabMultiple('#wiki-history .tablesorter tbody tr')));
		//$I->assertEquals(2,count($I->grabMultiple('#wiki-history .tablesorter tbody tr')));
		$I->click('Page History');
		codecept_debug(count($I->grabMultiple('#page-history .tablesorter tbody tr')));
		//$I->assertEquals(2,count($I->grabMultiple('#wiki-history .tablesorter tbody tr')));
	}
	/****************************************************************************
	 * TESTS
	 ***************************************************************************/	 
	public function testWiki($I) {
		try {
			$I->login($I,$this->username,$this->password);
			// CREATE wikis
			$this->createNewWiki($I,'Test RTE',false,'richtext');
			$I->assertEquals(1,$this->countVisibleWikis($I));
			$this->createNewWiki($I,'Test Markdown',true,'markdown');
			$I->assertEquals(2,$this->countVisibleWikis($I));
			
			$this->testWikiEditing($I);
			$this->testWikiHistory($I);
			$I->assertEquals(2,$this->countVisibleWikis());
			$this->createNewWiki($I,'For Trash',false,'richtext');
			$I->assertEquals(3,$this->countVisibleWikis());
			$this->deleteWiki($I,'For Trash');
			$I->assertEquals(2,$this->countVisibleWikis());
			
		} catch (Exception $e) {
			$I->fail($e->getMessage()." in ".$e->getFile()." line ".$e->getLine()."     ".$e->getTraceAsString());
		}
		return;
		
	}


	/****************************************************************************
	 * WIKI SUPPORT FUNCTIONS
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
			'check:is_public'=>true,
			'select:type' =>$type]);
		$I->click('Create');
		$I->see('Wiki '.$name.' created');	
		
	}
	
	private function countVisibleWikis($I) {
		//$I->amOnPage('/wiki');
		$I->click('Wiki');
		$I->click('Wiki');
		// only one list on wiki home page
		return count($I->grabMultiple('.tablesorter tbody tr'));
		//return $I->executeJS('return $(".tablesorter tbody tr").length');
	}
	
	private function viewWiki($I,$name) {
		$I->amOnPage('/wiki');
		$row = $I->findTableRowMatching($I,1,$name);
        $context=".tablesorter tbody tr:nth-child(". $row .")";
		$I->click($context.' a:nth-child(1)');
        $I->wait(1);
	}

	private function deleteWiki($I,$name) {
		$I->amOnPage('/wiki');
		$row = $I->findTableRowMatching($I,1,$name);
        $context=".tablesorter tbody tr:nth-child(". $row .")";
        $I->executeJS('window.confirm = function(){return true;}');
		$I->click($context.' .deletebutton');
        $I->wait(3);
        $I->see('Wiki deleted');
	}

	
	private function setBody($I,$text,$isMarkdown=false) {
		if ($isMarkdown) {
			$I->executeJS('simplemde.value("'.$text.'");');
			$I->executeJS('CodeMirror.signal(simplemde,"keyup");');
		} else {
			$I->executeJS('CKEDITOR.instances.body.setData("'.$text.'")');
			$I->executeJS('CKEDITOR.instances.body.document.fire("keyup")');
		}
	}
	
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
		// check that it is updated in the view
		$I->click('View');
		// hmm wikiword transforms mess me up here
		if (strlen(trim($checkContent))>0) $I->see($checkContent);
	}
//codecept_debug($this->countVisibleWikis($I));

	// Precondition: $I is on a wiki view page	
	private function createWikiMember($I,$userFullName) {
		$I->click('Members');
		$memberCount=count($I->grabMultiple('#members .tablesorter tbody tr'));
		$I->click('Add Member');
		$I->fillAutocomplete($I,'user_id',$userFullName);
		$I->selectOption('role','reader');
		$I->click('Save');
		//$I->see('Member updated');
		$I->assertEquals($memberCount+1,count($I->grabMultiple('#members .tablesorter tbody tr')));
	}
	
	// Precondition: $I is on a wiki view page
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
	
	// Precondition: $I is on a wiki view page (and members tab is visible)
	private function deleteWikiMember($I,$row) {
		$I->click('Members');
		$memberCount=count($I->grabMultiple('#members .tablesorter tbody tr'));
        $context="#members .tablesorter tbody tr:nth-child(". $row .")";
        $I->executeJS('window.confirm = function(){return true;}');
		$I->click($context.' .deletebutton');        
		$I->assertEquals($memberCount-1,count($I->grabMultiple('#members .tablesorter tbody tr')));
	}
		
		/*
		 
		 $nick = $I->haveFriend('nick');
$nick->does(function(AcceptanceTester $I) {
    $I->amOnPage('/messages/new');
    $I->fillField('body', 'Hello all!')
    $I->click('Send');
    $I->see('Hello all!', '.message');
});
		 
		  
		 * */
		 
	
}
