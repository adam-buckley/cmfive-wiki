<?php
function delwiki_GET(&$w) {
	try {
		$pm = $w->pathMatch("wid");
		$wiki = $w->Wiki->getWikiById($pm['wid']);
		if ($wiki  && ($wiki->isOwner($w->Auth->user()) || $w->Auth->user()->is_admin)) {
			$wiki->delete();
			$w->msg("Wiki deleted.","/wiki/");
		} else {
			$w->error("No access to delete this wiki.");
		}
	} catch (WikiException $ex) {
		$w->error($ex->getMessage(),"/wiki");
	}
}
