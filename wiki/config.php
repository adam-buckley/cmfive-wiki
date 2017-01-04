<?php
Config::set('wiki', array(
    'version' => '0.8.0',		
    'active' => true,
    'path' => 'modules',
    'topmenu' => true,
    'search' => array("Wiki Pages" => "WikiPage"),
    'dependencies' => array(
        'cebe/markdown' => '~1.0.1'
    ),
    'liveedit' => true,
	'hooks' => array(
		'wiki',
	),
));

// enable WikiPage in rest module
Config::append('system.rest_allow',array("WikiPage"));

// enable wikiPage to be mapped to forms
Config::append('form.mapping', [
 	'WikiPage'
]);
