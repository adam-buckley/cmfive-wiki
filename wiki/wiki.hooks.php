<?php

/**
 * Wiki Macro
 *
 * @@timestamp@@
 *
 * Replaces with a bold current timestamp, eg.
 * *07/01/2017 02:34 pm* for markdown and
 * html codes for html/richtext
 *
 * @param Web $w
 * @param Array[wiki:,page:,options:] $params
 */
function wiki_wiki_macro_timestamp_do(Web $w, $params)
{
    $wiki = $params['wiki'];
    $page = $params['page'];
    return date("d/m/Y h:i a");
}

/**
 * Wiki Macro
 *
 * @@userstamp@@
 *
 * replaces with user name and current timestamp, eg.
 * **Admin Admin, 07/01/2017 02:34 pm** for markdown and
 * html codes for html/richtext
 *
 * @param Web $w
 * @param Array[wiki:,page:,options:] $params
 */
function wiki_wiki_macro_userstamp_do(Web $w, $params)
{
    $wiki = $params['wiki'];
    $page = $params['page'];
    if ($wiki->type == "markdown") {
        return "**" . $w->Auth->user()->getFullName() . "," . date("d/m/Y h:i a") . "** ";
    } else {
        return "<b>" . $w->Auth->user()->getFullName() . "," . date("d/m/Y h:i a") . "</b>&nbsp;";
    }
}

/**
 * Wiki Shortcode
 *
 * [[page|ThisIsANewPage(|Optional Page Titel)]]
 *
 * @param Web $w
 * @param Array[wiki:,page:,options:] $params
 */
function wiki_wiki_shortcode_page_do(Web $w, $params)
{
    $wiki = $params['wiki'];
    $page = $params['page'];
    if (!empty($params['options'])) {
        $link = $params['options'][0];
        $title = empty($params['options'][1]) ? $link : $params['options'][1];
    }
    $link = "<a class='wikiwordlink wikiwordlink-" . $link . "' href='" . WEBROOT . '/wiki/view/' . $wiki->name . "/" . $link . "' >" . $title . "</a>";
    return $link;
}

/**
 * Wiki Shortcode
 *
 * [[link|http://thisisaurl.com(|Optional Link Titel)]]
 *
 * @param Web $w
 * @param Array[wiki:,page:,options:] $params
 */
function wiki_wiki_shortcode_link_do(Web $w, $params)
{
    $wiki = $params['wiki'];
    $page = $params['page'];
    if (!empty($params['options'])) {
        $link = $params['options'][0];
        $title = empty($params['options'][1]) ? $link : $params['options'][1];
    }
    $link = "<a class='wikilink' href='" . $link . "' >" . $title . "</a>";
    return $link;
}

/**
 * Wiki Shortcode
 *
 * [[video|{vimeo/youtube/local}|{id/filename}|{title}|{width}|{height}]]
 *
 * @param Web $w
 * @param Array[wiki:,page:,options:] $params
 */
function wiki_wiki_shortcode_video_do(Web $w, $params)
{
    $wiki = $params['wiki'];
    $page = $params['page'];
    if (!empty($params['options'])) {
        list($type, $id, $title) = $params['options'];
        $width = isset($params['options'][3]) ? $params['options'][3] : Config::get("wiki.shortcode.video.default_width", 640);
        $height = isset($params['options'][4]) ? $params['options'][4] : (int) ($width * Config::get("wiki.shortcode.video.default_height", 359) / Config::get("wiki.shortcode.video.default_width", 640));
        $playerid = preg_replace('/\s+/', '', $title);
        $title = urlencode($title);
    }
    switch ($type) {
        case "vimeo":
            return "
                   <div class=\"wikivideo\">
                <iframe id=\"{$playerid}\" data-progress=\"true\" data-seek=\"true\"
                    src=\"https://player.vimeo.com/video/{$id}?api=1&player_id={$playerid}&title={$title}&amp;byline=0&amp;portrait=0&amp;color=ffffff\"
                    frameborder=\"0\" width=\"{$width}\" height=\"{$height}\" webkitAllowFullScreen mozallowfullscreen allowFullScreen>
                </iframe>
                </div>";
        case "youtube":
            return "
                <div class=\"wikivideo\">
                    <iframe width=\"{$width}\" height=\"{$height}\" src=\"https://www.youtube.com/embed/{$id}\" frameborder=\"0\" allowfullscreen></iframe>
                </div>";
        default:
            return "";
    }
}

/**
 * Wiki Shortcode
 *
 * [[parents(|{list|flat})]]
 *
 * List all wiki pages which link to this page
 *
 * The optional format option is either:
 *
 * list - puts links into an html list (default)
 * flat - comma separated links on one line
 *
 * @param Web $w
 * @param Array[wiki:,page:,options:] $params
 */
function wiki_wiki_shortcode_parents_do(Web $w, $params)
{
    $wiki = $params['wiki'];
    $page = $params['page'];
    if (!empty($params['options'])) {
        $format = empty($params['options'][0]) ? "list" : $params['options'][0];
    }
    $parents = $page->getParents();
    $html = "";
    if (!empty($parents)) {
        foreach ($parents as $parent) {
            $link = "<a class='wikiwordlink wikiwordlink-" . $parent->name . "' href='" . WEBROOT . '/wiki/view/' . $wiki->name . "/" . $parent->name . "' >" . $parent->name . "</a>";
            $html += $format == "list" ? "<li>" . $link . "</li>" : $link . ", ";
        }
    }
    if (!empty($html)) {
        $html = $format == "list" ? "<ul>" . $html . "</ul>" : "<p>" . substr($html, 0, -2) . "</p>";
    }
    return $html;
}
