<?php

function delwiki_GET(&$w)
{
    try {
        $pm = $w->pathMatch("wid");
        $wiki = WikiService::getInstance($w)->getWikiById($pm['wid']);
        if ($wiki  && ($wiki->isOwner(AuthService::getInstance($w)->user()) || AuthService::getInstance($w)->user()->is_admin)) {
            $wiki->delete();
            $w->msg("Wiki deleted.", "/wiki/");
        } else {
            $w->error("No access to delete this wiki.");
        }
    } catch (WikiException $ex) {
        $w->error($ex->getMessage(), "/wiki");
    }
}
