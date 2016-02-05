<?php

function edit_POST(Web &$w) {
	try {
		$pm = $w->pathMatch("wikiname","pagename");
		$wiki = $w->Wiki->getWikiByName($pm['wikiname']);
		if (!$wiki) {
			$w->error("Wiki does not exist.");
		}
		$wp = $wiki->getPage($pm['pagename']);
		if (!$wp) {
			$w->error("Page does not exist.");
		}
		$wiki->updatePage($pm['pagename'],$w->request("body"));
		$w->msg("Page updated.","/wiki/view/".$pm['wikiname']."/".$pm['pagename']);
	} catch (WikiException $ex) {
		$w->error($ex->getMessage(),"/wiki");
	}
}
