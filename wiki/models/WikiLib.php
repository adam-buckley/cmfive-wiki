<?php

class WikiLib
{
    /**
     * Returns HTML transformed from Markdown markup
     * @param $page
     */
    public static function wiki_format_cebe($wiki, $page)
    {
        $parser = new \cebe\markdown\Markdown();
        $body = $page->body;
        $body = $parser->parse($body);
        return $body;
    }

    /**
     * This function replaces wiki shortcode by calling matching hook functions.
     * Shortcodes are evaluated before DISPLAYING a wiki page
     * For example:
     * [[page|NewPage|An optional page title]]
     *
     * will call:
     * $w->callHook("wiki","shortcode_page_do",[$wiki,$page,$options]);
     *
     * a catching function will look like:
     * function {module}_wiki_shortcode_page_do($web,$params);
     *
     *
     * @param unknown $wiki
     * @param unknown $page
     * @param unknown $text
     * @param string $prefix
     * @param string $suffix
     * @return mixed
     */
    public static function replaceWikiCode($wiki, $page, $text, $prefix = "\[\[", $suffix = "\]\]")
    {
        return preg_replace_callback(
            "/" . $prefix . "(.*?)((?:\|.*?)*)" . $suffix . "/",
            function ($matches) use ($wiki, $page) {
                $hook = "shortcode_" . $matches[1] . "_do";
                $params = explode("|", $matches[2]);
                array_shift($params);
                $replacements = $wiki->w->callHook("wiki", $hook, ["wiki" => $wiki, "page" => $page, "options" => $params]);
                return empty($replacements) ? "" : implode(" ", $replacements);
            },
            $text
        );
    }

    /**
     * This function replaces wiki macros by calling matching hook functions.
     * Macros are evaluated before UPDATING the body of a wiki page.
     * Therefore macros have to take into account the TYPE of a wiki and create
     * matching code, eg. markdown or html (richtext).
     *
     * Macros can also be turned into shortcodes!
     *
     * For example:
     * @@userstamp@@
     *
     * will call:
     * $w->callHook("wiki","macro_userstamp_do",[$wiki,$page,$options]);
     *
     * a catching function will look like:
     * function {module}_wiki_macro_userstamp_do($web,$params);
     *
     * which will replace the macro with the current user's name and the current time.
     *
     * @param unknown $wiki
     * @param unknown $page
     * @param unknown $text
     * @param string $prefix
     * @param string $suffix
     * @return mixed
     */
    public static function replaceWikiMacros($wiki, $page, $text, $prefix = "\@\@", $suffix = "\@\@")
    {
        return preg_replace_callback(
            "/" . $prefix . "(.*?)((?:\|.*?)*)" . $suffix . "/",
            function ($matches) use ($wiki, $page) {
                $hook = "macro_" . $matches[1] . "_do";
                $params = explode("|", $matches[2]);
                array_shift($params);
                $replacements = $wiki->w->callHook("wiki", $hook, ["wiki" => $wiki, "page" => $page, "options" => $params]);
                return empty($replacements) ? "" : implode(" ", $replacements);
            },
            $text
        );
    }
}
