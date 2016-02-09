<?php

function ajaxsavepage_POST(Web &$w) {
	try {
		$w->setLayout(null);
		
		$pm = $w->pathMatch("wikiname","pagename","last_modified");
		$wiki = $w->Wiki->getWikiByName($pm['wikiname']);
		if ($wiki) {
			$wp = $wiki->getPage($pm['pagename']);
			if ($wp) {
				//echo $wp->dt_modified."||||".$pm['last_modified'];
				if ($wp->dt_modified > $pm['last_modified']) {
					echo $wp->dt_modified.":::DT_MODIFIED:::".$wp->body;
				} else {
					$wiki->updatepage($pm['pagename'],$w->request('body'));
					$uwp = $wiki->getPage($pm['pagename']);
					echo $uwp->dt_modified;
				}
			}
			
		}
		
		//$wiki->updatePage($pm['pagename'],$w->request("body"));
		//echo "Page updated ".$wp->dt_modified;
		//$w->msg("Page updated.","/wiki/view/".$pm['wikiname']."/".$pm['pagename']);
	} catch (WikiException $ex) {
		echo $ex->getMessage();
	}
}
