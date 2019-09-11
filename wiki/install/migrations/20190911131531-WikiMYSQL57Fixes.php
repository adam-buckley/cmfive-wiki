<?php

class WikiMYSQL57Fixes extends CmfiveMigration
{
    public function up()
    {
        $this->changeColumnInTable("wiki", "last_modified_page_id", "integer", ["null" => true, "default" => null]);
        $this->changeColumnInTable("wiki_page", "body", "text", ["limit" => 4294967295, "null" => true, "default" => null]);
    }

    public function down()
    {
        // DOWN
    }
}
