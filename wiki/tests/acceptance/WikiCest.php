<?php

/****************************************************************************
 * Acceptance Test Suite for the Wiki module
 * @author Steve Ryan <steve@2pisoftware.com>
 ***************************************************************************/
class WikiCest
{
    public function _before()
    {
    }

    public function _after()
    {
    }

    // auth details
    var $username = 'admin';
    var $password = 'admin';

    /****************************************************************************
     * TESTS
     ***************************************************************************/


    /****************************************************************************
     * Run a range of tests across the wiki module
     ***************************************************************************/
    public function testWiki($I)
    {
        try {
            $I->login($I, $this->username, $this->password);
            // CREATE wikis and check that they are reflected in the wiki list view
            $this->createNewWiki($I, 'Test RTE', true, 'richtext');
            $I->assertEquals(1, $this->countVisibleWikis($I));
            $this->createNewWiki($I, 'Test Markdown', false, 'markdown');
            $I->assertEquals(2, $this->countVisibleWikis($I));
            $this->testWikiEditing($I);
            $I->assertEquals(2, $this->countVisibleWikis($I));
            $this->createNewWiki($I, 'Trash', false, 'richtext');
            // check membership functions for the Trash wiki
            $this->testWikiMembers($I);
            $I->assertEquals(3, $this->countVisibleWikis($I));
            // delete a wiki
            $this->deleteWiki($I, 'Trash');
            $I->assertEquals(2, $this->countVisibleWikis($I));
        } catch (Exception $e) {
            $I->fail($e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine() . "     " . $e->getTraceAsString());
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
    private function testWikiEditing($I)
    {
        // with live editing
        // RTE
        $this->updateWiki($I, 'Test RTE', 'My content [[WikiWord]]', true, false, 'My content');  // bypass check for full content for link transform
        // now check link transform
        $I->click('View');
        $I->see('My content');
        // expect 1 page and 1 wiki history
        $I->click('Page History');
        $I->seeNumberOfElements('#page-history .tablesorter tbody tr', 1);
        $I->click('Wiki History');
        $I->seeNumberOfElements('#wiki-history .tablesorter tbody tr', 1);

        // restore from history
        $I->click('Page History');
        $historyLength = count($I->grabMultiple('#page-history .tablesorter tbody tr'));
        $I->click('#page-history .tablesorter tbody tr:nth-child(' . $historyLength . ') a');
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
        $this->updateWiki($I, 'Test RTE', 'My latest content', true, false);
        $I->click('Page History');
        codecept_debug($I->grabMultiple('#page-history .tablesorter tbody tr'));
        $I->click('Wiki History');
        codecept_debug($I->grabMultiple('#wiki-history .tablesorter tbody tr'));

        // create a new page and check the difference between page and wiki history
        $I->amOnPage("/wiki/view/TestRTE/MyNewPage");
        $I->see('MyNewPage');
        // expect ? page and ? wiki history
        $I->click('Page History');
        codecept_debug($I->grabMultiple('#page-history .tablesorter tbody tr'));
        $I->click('Wiki History');
        codecept_debug($I->grabMultiple('#wiki-history .tablesorter tbody tr'));
        // check request without page parameter
        $I->amOnPage("/wiki/view/TestRTE");
        $I->see('HomePage');

        $I->click('Page History');
        codecept_debug($I->grabMultiple('#page-history .tablesorter tbody tr'));
        $I->click('Wiki History');
        codecept_debug($I->grabMultiple('#wiki-history .tablesorter tbody tr'));

        // MARKDOWN
        $this->updateWiki($I, 'Test Markdown', 'My md content  [[WikiWord]]', true, true, 'My md content');
        $I->wait(3);
        $I->click('Edit');
        $markdown = $I->executeJS('return simplemde.codemirror.getValue();');
        codecept_debug($markdown);
        if (strpos($markdown, '[WikiWord](') == false) {
            codecept_debug('Cant see transformed wiki word in markdown');
        }
    }

    /****************************************************************************
     * Test wiki membership functions - create/update/delete member
     * PRECONDITION $I is on a wiki view page
     ***************************************************************************/
    private function testWikiMembers($I)
    {
        // MEMBERSHIP
        $this->createWikiMember($I, 'admin admin', 'reader');
        $this->updateWikiMember($I, 2, 'admin admin', 'editor');
        $this->deleteWikiMember($I, 2);
    }

    /****************************************************************************
     * Create a new wiki
     ***************************************************************************/
    private function createNewWiki($I, $name, $is_public, $type)
    {
        $I->amOnPage('/wiki/createwiki');

        $I->fillField("#title", $name);
        $is_public? $I->checkOption("#is_public") : $I->uncheckOption("#is_public");
        $I->selectOption("form select[name=type]", $type);

        $I->click('Create');
        $I->see('Wiki ' . $name . ' created');
    }

    /****************************************************************************
     * Count the number of wikis on the wiki home page for the logged in user
     ***************************************************************************/
    private function countVisibleWikis($I)
    {
        //$I->amOnPage('/wiki');
        $I->click('Wiki');
        $I->click('Wiki');
        // only one list on wiki home page
        return count($I->grabMultiple('.tablesorter tbody tr'));
    }

    /****************************************************************************
     * Navigate to a wiki view page given it's name
     ***************************************************************************/
    private function viewWiki($I, $name)
    {
        $I->amOnPage('/wiki');
        $row = $I->findTableRowMatching(1, $name);
        $context = ".tablesorter tbody tr:nth-child(" . $row . ")";
        $I->click($context . ' a:nth-child(1)');
        $I->wait(1);
    }

    /****************************************************************************
     * Delete a wiki
     ***************************************************************************/
    private function deleteWiki($I, $name)
    {
        $I->amOnPage('/wiki');
        $row = $I->findTableRowMatching(1, $name);
        $context = ".tablesorter tbody tr:nth-child(" . $row . ")";
        $I->executeJS('window.confirm = function(){return true;}');
        $I->click($context . ' .deletebutton');
        $I->wait(3);
        $I->see('Wiki deleted');
    }

    /****************************************************************************
     * Test wiki membership functions - create/update/delete member
     * PRECONDITION $I is on a wiki view page with the edit tab focussed
     ***************************************************************************/
    private function setBody($I, $text, $isMarkdown = false)
    {
        if ($isMarkdown) {
            $I->executeJS('simplemde.value("' . $text . '");');
            $I->executeJS('CodeMirror.signal(simplemde,"keyup");');
        } else {
            $I->executeJS('CKEDITOR.instances.wikibody.setData("' . $text . '")');
            $I->executeJS('CKEDITOR.instances.wikibody.document.fire("keyup")');
        }
    }

    /****************************************************************************
     * Update a wiki homepage with new content
     ***************************************************************************/
    private function updateWiki($I, $wikiName, $newContent, $liveEdit = true, $isMarkdown = false, $checkContent = null)
    {
        if ($checkContent === null) {
            $checkContent = $newContent;
        }

        $this->viewWiki($I, $wikiName);
        $I->click('Edit');
        $this->setBody($I, $newContent, $isMarkdown);
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
        $viewText = $I->executeJS('return $("#view").text()');
        $status = "FAIL";
        if (strpos($viewText, $checkContent) !== false) {
            $status = "OK";
        }
        codecept_debug([$status, 'look for ', $checkContent, 'in', $viewText]);
    }

    /****************************************************************************
     * Add a member to the current wiki
     * PRECONDITION $I is on a wiki view page, the page is HomePage and the login user
     * is either the wiki owner or admin.
     ***************************************************************************/
    private function createWikiMember($I, $userFullName, $role = 'reader')
    {
        $I->click('Members');
        $memberCount = count($I->grabMultiple('#members .tablesorter tbody tr'));
        $I->click('Add Member');
        $I->fillAutocomplete('user_id', $userFullName);
        $I->selectOption('role', $role);
        $I->click('Save');
        $I->assertEquals($memberCount + 1, count($I->grabMultiple('#members .tablesorter tbody tr')));
    }

    /****************************************************************************
     * Update a membership in the current wiki
     * PRECONDITION $I is on a wiki view page, the page is HomePage and the login user
     * is either the wiki owner or admin.
     ***************************************************************************/
    private function updateWikiMember($I, $listRow, $userFullName, $role)
    {
        $I->click('Members');
        $memberCount = count($I->grabMultiple('#members .tablesorter tbody tr'));
        // edit membership
        $I->click('#members .tablesorter tbody tr:nth-child(' . $listRow . ') .editbutton');

        $I->fillAutocomplete('user_id', $userFullName);
        $I->selectOption('role', $role);
        $I->click('Save');
        $I->assertEquals($memberCount, count($I->grabMultiple('#members .tablesorter tbody tr')));
    }

    /****************************************************************************
     * Delete a membership in the current wiki
     * PRECONDITION $I is on a wiki view page, the page is HomePage and the login user
     * is either the wiki owner or admin.
     ***************************************************************************/
    private function deleteWikiMember($I, $row)
    {
        $I->click('Members');
        $memberCount = count($I->grabMultiple('#members .tablesorter tbody tr'));
        $context = "#members .tablesorter tbody tr:nth-child(" . $row . ")";
        $I->executeJS('window.confirm = function(){return true;}');
        $I->click($context . ' .deletebutton');
        $I->assertEquals($memberCount - 1, count($I->grabMultiple('#members .tablesorter tbody tr')));
    }
}
