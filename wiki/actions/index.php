<?php

function index_ALL(Web $w)
{
    $wiki = $page = null;
    WikiService::getInstance($w)->navigation($w, $wiki, $page);
    History::add("Wiki List");
    try {
        $w->ctx("wikis", WikiService::getInstance($w)->getWikis());
    } catch (Exception $ex) {
        $w->error($ex->getMessage(), "/");
    }
}
