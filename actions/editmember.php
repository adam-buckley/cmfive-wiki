<?php

function editmember_GET(Web &$w)
{
    try {
        $pm = $w->pathMatch("wid", "mid");
        $wiki = WikiService::getInstance($w)->getWikiById($pm['wid']);
        if (!$wiki || !$wiki->isOwner(AuthService::getInstance($w)->user())) {
            $w->error("No access to this wiki.");
        }
        $mem = $wiki->getUserById($pm['mid']);
        if (!$mem) {
            $mem = new WikiUser($w);
        }
        $w->ctx("wiki", $wiki);
        $w->ctx("mem", $mem);
    } catch (WikiException $ex) {
        $w->error($ex->getMessage(), "/wiki");
    }
}

function editmember_POST(&$w)
{
    try {
        $pm = $w->pathMatch("wid", "mid");
        $wiki = WikiService::getInstance($w)->getWikiById($pm['wid']);
        if (!$wiki || !$wiki->isOwner(AuthService::getInstance($w)->user())) {
            $w->error("No access to this wiki.");
        }
        $mem = $wiki->getUserById($pm['mid']);
        if (!$mem) {
            $mem = new WikiUser($w);
        }
        $mem->user_id = $w->request("user_id");
        $mem->role = $w->request("role");
        $mem->wiki_id = $wiki->id;
        $mem->insertOrUpdate();
        $w->msg("Member updated.", "/wiki/view/" . $wiki->name . "/HomePage#members");
    } catch (WikiException $ex) {
        $w->error($ex->getMessage(), "/wiki");
    }
}
