<?php

/*********************************************
 * Save POST updates to body field into WikiPage
 *********************************************/
function preview_GET(Web &$w)
{
    try {
        $pm = $w->pathMatch("wikiname", "pagename");
        $wiki = WikiService::getInstance($w)->getWikiByName($pm['wikiname']);
        if (!$wiki) {
            $w->error("Wiki does not exist.");
        }
        $wp = $wiki->getPage($pm['pagename']);
        if (!$wp) {
            $w->error("Page does not exist.");
        }
        $w->setLayout(null);
        $body = "";
        if ($wiki->type == "richtext") {
            $body = $wp->body;
        } elseif ($wiki->type == "markdown") {
            $body = WikiLib::wiki_format_cebe($wiki, $wp);
        }
        $body = WikiLib::replaceWikiCode($wiki, $wp, $body);
        echo $body;
    } catch (WikiException $ex) {
        $w->error($ex->getMessage(), "/wiki");
    }
}
