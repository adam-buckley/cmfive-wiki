<?php

function delmember_GET(&$w)
{
    try {
        $pm = $w->pathMatch("wid", "mid");
        $wiki = WikiService::getInstance($w)->getWikiById($pm['wid']);
        if (!$wiki || !$wiki->isOwner(AuthService::getInstance($w)->user())) {
            $w->error("No access to delete this wiki.");
        }
        $mem = $wiki->getUserById($pm['mid']);
        if (!$mem) {
            // oh well
        } else {
            $mem->delete();
            $w->msg("Member removed.", "/wiki/view/" . $wiki->name . "/HomePage#members");
        }
    } catch (WikiException $ex) {
        $w->error($ex->getMessage(), "/wiki");
    }
}
