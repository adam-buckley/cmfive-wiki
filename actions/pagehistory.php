<?php

function pagehistory_GET(Web &$w)
{
    // AJAX ENDPOINT
    $w->setLayout(null);
    try {
        $pm = $w->pathMatch("wikiname", "pagename");

        // Check for missing parameter
        if (empty($pm["wikiname"])) {
            $w->error("Wiki does not exist.", "/wiki");
        }

        // Get wiki object and check for existance
        $wiki = $w->Wiki->getWikiByName($pm['wikiname']);
        // Register for timelog

        if (empty($wiki->id)) {
            $w->error("Wiki does not exist.");
        }

        // If page doesn't exist, make one
        if (strlen(trim($pm['pagename'])) == '') {
            $pm['pagename'] = "HomePage";
        }
        $wp = $wiki->getPage($pm['pagename']);
        if (!$wp) {
            $w->error('Page does not exist');
        }

        // Set navigation
        $w->Wiki->navigation($w, $wiki, $pm["pagename"]);

        $w->ctx("wiki", $wiki);
        $w->ctx("page", $wp);
        $w->ctx("hist", $wp->getHistory());
        $w->ctx("title", $wiki->title . " - " . $wp->name);
    } catch (Exception $e) {
        $w->error($e->getMessage(), "/wiki");
    }
}
