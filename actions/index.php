<?php

function index_ALL(Web $w)
{
    $wiki = $page = null;
    $w->Wiki->navigation($w, $wiki, $page);
    History::add("Wiki List");
    try {
        $w->ctx("wikis", $w->Wiki->getWikis());
    } catch (Exception $ex) {
        $w->error($ex->getMessage(), "/");
    }
}
