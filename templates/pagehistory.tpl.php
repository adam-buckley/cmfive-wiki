<?php

$table = [];

if ($hist) {
    $table[] = ["Date", "User", "Action"];

    foreach ($hist as $ph) {
        $table[] = [
            formatDateTime($ph->dt_created),
            $w->Auth->getUser($ph->creator_id)->getFullName(),
            Html::a(WEBROOT . "/wiki/viewhistoryversion/" . $wiki->name . "/" . $page->name . "/" . $ph->id, "View", true),
        ];
    }
    echo Html::table($table, "history", "tablesorter", true);
} else {
    echo "No changes yet.";
}
