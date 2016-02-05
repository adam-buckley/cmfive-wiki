<?php
$lines = array(
    "Create Wiki" => array(
        array(array("Title","text","title","")),
        array(array("Public","checkbox","is_public",0)),
         array(array("Type","select","type",'markdown',[['Text','text'],['Rich Text','richtext'],['Markdown','markdown'],['Mind Map','mindmap']]))
    )
);

echo Html::multiColForm($lines,$w->localUrl("/wiki/createwiki"),"POST","Create");
