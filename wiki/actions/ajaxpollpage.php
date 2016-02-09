<?php

function ajaxpollpage_GET(Web &$w) {
	try {
		$w->setLayout(null);
		
		$pm = $w->pathMatch("wikiname","pagename","last_modified");
		$wiki = $w->Wiki->getWikiByName($pm['wikiname']);
		// long polling (DISABLED because it caused problems with view page load)
		$maxChecks=1;
		$i=0;
		while ($i <$maxChecks) {
			if ($wiki) {
				$wp = $wiki->getPage($pm['pagename']);
				if ($wp) {
					//echo $wp->dt_modified."||||".$pm['last_modified'];
					if ($wp->dt_modified > $pm['last_modified']) {
						$uwp = $wiki->getPage($pm['pagename']);
						echo $wp->dt_modified;
						echo ":::DT_MODIFIED:::";
						echo $wp->body;
						
					}
				}
				
			}
			$i++;
			echo "    ";
			flush();
			//sleep(2);
		}
	} catch (WikiException $ex) {
		echo $ex->getMessage();
	}
}
