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
