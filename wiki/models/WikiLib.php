<?php

class WikiLib {
	
	/**
     * Returns HTML transformed from Markdown markup
     * @param $page
     */
    public static function wiki_format_cebe($wiki, $page) {
		$parser = new \cebe\markdown\Markdown();
		$body=$page->body;
        $body = $parser->parse($body);
        return $body;
    }
    
    public static function replaceWikiCode($wiki,$page,$text,$prefix="\[\[",$suffix="\]\]") {
    	return preg_replace_callback("/".$prefix."(.*?)((?:\|.*?)*)".$suffix."/", 
    			function ($matches) use ($wiki, $page) {
			    	$hook = "shortcode_".$matches[1]."_do";
			    	$params = explode("|", $matches[2]);
			    	array_shift($params);
			    	return $wiki->w->callHook("wiki",$hook,["wiki"=>$wiki,"page"=>$page,"options"=>$params]);
        		}, $text);
    }
    
    
}
