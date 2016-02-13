<?php
function viewhistoryversion_POST(Web &$w) {
    try {
		$pm = $w->pathMatch("wikiname", "pagename","historyid");
		
		// Check for missing parameter
		if (empty($pm["wikiname"])) {
			$w->error("Wiki does not exist.", "/wiki");
		}
		
		// Get wiki object and check for existance
		$wiki = $w->Wiki->getWikiByName($pm['wikiname']);
		    // Register for timelog
		
		if (empty($wiki->id)) {
			$w->error("Wiki does not exist.");
		}

		// If page doesn't exist, make one
		if (strlen(trim($pm['pagename']))=='') {
			$pm['pagename']="HomePage";
		}
		$wp = $wiki->getPage($pm['pagename']);
		if (!$wp) {
			$w->error('Page does not exist');
		}
		
		// Reset wiki breadcrumbs
		if ($pm['pagename'] == "HomePage") {
			$_SESSION['wikicrumbs'][$pm['wikiname']] = array();
		} else {
			$_SESSION['wikicrumbs'][$pm['wikiname']][$pm['pagename']] = 1;
		}

		// Set navigation
		$w->Wiki->navigation($w, $wiki, $pm["pagename"]);
		
		$wikiHistory=$w->Wiki->getObject("WikiPageHistory",$pm["historyid"]);
		if (!$wikiHistory)  {
			$w->error('No such history item');
		}
		// override body with history version
		$wp->body=$wikiHistory->body;
		$wp->update();
		$w->redirect($w->localUrl("/wiki/view/".$pm["wikiname"]."/".$pm["pagename"]));
		
	} catch (Exception $e) {
		$w->error($e->getMessage(),"/wiki");
	}
}

function viewhistoryversion_GET(Web &$w) {
    try {
		$pm = $w->pathMatch("wikiname", "pagename","historyid");
		
		// Check for missing parameter
		if (empty($pm["wikiname"])) {
			$w->error("Wiki does not exist.", "/wiki");
		}
		
		// Get wiki object and check for existance
		$wiki = $w->Wiki->getWikiByName($pm['wikiname']);
		    // Register for timelog
		$w->Timelog->registerTrackingObject($wiki);
		
		if (empty($wiki->id)) {
			$w->error("Wiki does not exist.");
		}

		// If page doesn't exist, make one
		if (strlen(trim($pm['pagename']))=='') {
			$pm['pagename']="HomePage";
		}
		$wp = $wiki->getPage($pm['pagename']);
		if (!$wp) {
			$w->error('Page does not exist');
		}
		
		// Reset wiki breadcrumbs
		if ($pm['pagename'] == "HomePage") {
			$_SESSION['wikicrumbs'][$pm['wikiname']] = array();
		} else {
			$_SESSION['wikicrumbs'][$pm['wikiname']][$pm['pagename']] = 1;
		}

		// Set navigation
		$w->Wiki->navigation($w, $wiki, $pm["pagename"]);
		
		$wikiHistory=$w->Wiki->getObject("WikiPageHistory",$pm["historyid"]);
		if (!$wikiHistory)  {
			$w->error('No such history item');
		}
		// override body with history version
		$wp->body=$wikiHistory->body;
		
		// Set edt wiki form
		$editForm = array(
			"" => array(
				array(array("", "textarea", "body", $wp->body, 60, 24, false))
			)
		);
		
		// Set template vars
		if ($w->type=="richtext") {
			$w->ctx("body",$wp->body);
			
		} else  {  // richtext etc
			$w->ctx("body", WikiLib::wiki_format_cebe($wiki, $wp));
		}
		$w->ctx("wiki", $wiki);
		$w->ctx("page", $wp);
		$w->ctx("history", $wikiHistory);
		$w->ctx("title", $wiki->title . " - " . $wp->name);
		
	
	} catch (Exception $e) {
		$w->error($e->getMessage(),"/wiki");
	}
}
