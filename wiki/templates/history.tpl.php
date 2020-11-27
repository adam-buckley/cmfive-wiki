<?php

$table = [];

if (!empty($hist)) {
    $table[] = ["Date", "Page", "User"];
    foreach ($hist as $wh) {
        $table[] = [
            formatDateTime($wh["dt_created"]),
            Html::a(WEBROOT . "/wiki/viewhistoryversion/" . $wiki->name . "/" . $page->name . "/" . $wh['id'], "<b>" . $page->name . "</b>"),
            AuthService::getInstance($w)->getUser($wh['creator_id'])->getFullName()
        ];
    }
    echo Html::table($table, "history", "tablesorter", true);
} else {
    echo "No changes yet.";
}
