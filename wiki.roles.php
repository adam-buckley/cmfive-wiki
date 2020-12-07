<?php

function role_wiki_user_allowed(Web $w, $path)
{
    return  $w->checkUrl($path, "wiki", null, "*");
}
