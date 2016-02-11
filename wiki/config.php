<?php

Config::set('wiki', array(
    'version' => '0.8.0',		
    'active' => true,
    'path' => 'modules',
    'topmenu' => true,
    'search' => array("Wiki Pages" => "WikiPage"),
    'dependencies' => array(
        'cebe/markdown' => '~1.0.1'
    )
));
// enable WikiPage in rest module
$restAllow=Config::get('system.rest_allow');
if (!is_array($restAllow))  {
	$restAllow=[];
}
Config::set('system.rest_allow',array_merge($restAllow, array("WikiPage")));


