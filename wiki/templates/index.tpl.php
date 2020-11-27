<?php

$wikiCount = 0;

if (!empty($wikis)) {
    $table[] = [
        "Wiki Title",
        "Type",
        "Date Created",
        "Last Modified Date",
        "Modified By",
        "Last Page Modified",
        ""
    ];

    foreach ($wikis as $wi) {
        if ($wi->canView(AuthService::getInstance($w)->user())) {
            $wikiCount++;
            $lastModifiedPage = "";
            $lastModifiedPageUser = "";
            $lastModifiedDate = "";
            if ($wi->last_modified_page_id > 0) {
                $p = $wi->getPageById($wi->last_modified_page_id);
                if (!empty($p)) {
                    $wuser = AuthService::getInstance($w)->getUser($p->modifier_id);
                    $lastModifiedPageUser = empty($wuser) ? '' : AuthService::getInstance($w)->getUser($p->modifier_id)->getFullName();
                    $lastModifiedPage = $p->name;
                    $lastModifiedDate = $p->dt_modified;
                }
            }
            $delLink = "";
            if ($wi->canDelete(AuthService::getInstance($w)->user())) {
                $delLink = Html::ab(WEBROOT . "/wiki/delwiki/" . $wi->id, 'Delete', 'deletebutton', '', 'Do you really want to delete this wiki and all of its pages?');
            }
            $table[] = [
                Html::a(WEBROOT . "/wiki/view/" . urlencode($wi->name) . "/HomePage", "<b>" . $wi->title . "</b>"),
                $wi->type,
                formatDateTime(0 + $wi->dt_created),
                formatDateTime(0 + $lastModifiedDate),
                $lastModifiedPageUser,
                Html::a(WEBROOT . "/wiki/view/" . $wi->name . "/" . $lastModifiedPage, $lastModifiedPage), $delLink
            ];
        }
    }
    echo Html::table($table, "wikilist", "tablesorter", true);
}

if ($wikiCount == 0) {
    echo "There are no wikis yet.<br>";
    echo Html::ab(WEBROOT . "/wiki/createwiki/", 'Create a wiki');
}
