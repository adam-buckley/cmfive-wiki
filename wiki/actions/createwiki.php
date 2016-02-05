<?php

function createwiki_GET(Web &$w) {
//	$w->setLayout(null);
}

function createwiki_POST(Web &$w) {
	$title = $w->request("title");
	$is_public = $w->request("is_public");
	$type = $w->request("type");
	try {
		$wiki = $w->Wiki->createWiki($title, $is_public,$type);
	} catch (WikiException $ex) {
		$w->error($ex->getMessage(),"/wiki/createwiki");
	}
	if ($wiki) {
		$w->msg("Wiki ".$title." created.","/wiki/view/".$wiki->name."/HomePage");
	} else {
		$w->error("Wiki couldn't be created","/wiki/index");
	}
}
