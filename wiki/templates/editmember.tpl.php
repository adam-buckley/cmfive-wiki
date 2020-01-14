<?php

$lines[] = ["Wiki Member", "section"];
$lines[] = ["User", "autocomplete", "user_id", $mem->user_id, $w->Auth->getUsersForRole("wiki_user")];
$lines[] = ["Role", "select", "role", $mem->role, array("reader", "editor")];

echo Html::form($lines, $w->localUrl("/wiki/editmember/" . $wiki->id . "/" . $mem->id), "POST", "Save");
