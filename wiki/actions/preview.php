<?php
/*********************************************
 * Save POST updates to body field into WikiPage
 *********************************************/
function preview_GET(Web &$w) {
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
		$w->setLayout(null);
		if ($wiki->type=="richtext") {
			echo $wp->body;
		} else {
			$parser = new \cebe\markdown\Markdown();
			$body=$wp->body;
			echo $parser->parse($body);
		}
		
		
		
	} catch (WikiException $ex) {
		$w->error($ex->getMessage(),"/wiki");
	}
}
