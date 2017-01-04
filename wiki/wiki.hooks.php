<?php

/**
 * Wiki Shortcode: [[link|ThisIsANewPage(|Optional Page Titel)]]
 * 
 * @param Web $w
 * @param Array[wiki:,page:,options:] $params
 */
function wiki_wiki_shortcode_page_do(Web $w, $params) {
	$wiki = $params['wiki'];
	$page = $params['page'];
	if (!empty($params['options'])) {
		$link = $params['options'][0];
		$title = empty($params['options'][1]) ? $link : $params['options'][1];
	}
	$link="<a class='wikiwordlink wikiwordlink-".$link."' href='".WEBROOT .'/wiki/view/'.$wiki->name."/".$link."' >".$title."</a>";
	return $link;
}