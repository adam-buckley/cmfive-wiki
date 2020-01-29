<?php
$lines = [
    "Create Wiki" => [
        [
            ["Title", "text", "title", ""],
        ],
        [
            ["Public", "checkbox", "is_public", 0],
        ],
        [
            ["Type", "select", "type", 'markdown', [['Rich Text', 'richtext'], ['Markdown', 'markdown']]],
        ]
    ]
];

echo Html::multiColForm($lines, $w->localUrl("/wiki/createwiki"), "POST", "Create");
