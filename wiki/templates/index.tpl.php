<?php

$wikiCount = 0;

if (!empty($wikis)) {
    $table[] = array(
        "Wiki Title",
        "Type",
        "Date Created",
        "Last Modified Date",
        "Modified By",
        "Last Page Modified",
        ""
    );

    foreach ($wikis as $wi) {
        if ($wi->canView($w->Auth->user())) {
            $wikiCount++;
            $lastModifiedPage = "";
            $lastModifiedPageUser = "";
            $lastModifiedDate = "";
            if ($wi->last_modified_page_id > 0) {
                $p = $wi->getPageById($wi->last_modified_page_id);
                if (!empty($p)) {
                    $wuser = $w->Auth->getUser($p->modifier_id);
                    $lastModifiedPageUser = empty($wuser) ? '' : $w->Auth->getUser($p->modifier_id)->getFullName();
                    $lastModifiedPage = $p->name;
                    $lastModifiedDate = $p->dt_modified;
                }
            }
            $delLink = "";
            if ($wi->canDelete($w->Auth->user())) {
                $delLink = Html::ab(WEBROOT . "/wiki/delwiki/" . $wi->id, 'Delete', 'deletebutton', '', 'Do you really want to delete this wiki and all of its pages?');
            }
            $table[] = array(
                Html::a(WEBROOT . "/wiki/view/" . urlencode($wi->name) . "/HomePage", "<b>" . $wi->title . "</b>"),
                $wi->type,
                formatDateTime(0 + $wi->dt_created),
                formatDateTime(0 + $lastModifiedDate),
                $lastModifiedPageUser,
                Html::a(WEBROOT . "/wiki/view/" . $wi->name . "/" . $lastModifiedPage, $lastModifiedPage), $delLink
            );
        }
    }
    echo Html::table($table, "wikilist", "tablesorter", true);
}

if ($wikiCount == 0) {
    echo "There are no wikis yet.<br>";
    echo Html::ab(WEBROOT . "/wiki/createwiki/", 'Create a wiki');
}
