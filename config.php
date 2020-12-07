<?php

Config::set('wiki', [
    'active' => true,
    'path' => 'modules',
    'topmenu' => true,
    'search' => ["Wiki Pages" => "WikiPage"],
    'dependencies' => [
        'cebe/markdown' => '1.1.*'
    ],
    'liveedit' => true,
    'hooks' => [
        'wiki',
    ],
    'shortcode' => [
        'video' => [
            'default_width' => 640,
            'default_height' => 359,
        ]
    ]
]);

// enable WikiPage in rest module
Config::append('system.rest_allow', ["WikiPage"]);

// enable wikiPage to be mapped to forms
Config::append('form.mapping', [
    'WikiPage'
]);
