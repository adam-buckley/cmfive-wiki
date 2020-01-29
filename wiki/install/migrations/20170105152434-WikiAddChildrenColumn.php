<?php

class WikiAddChildrenColumn extends CmfiveMigration
{
    public function up()
    {
        $table = $this->table('wiki_page');
        if (!empty($table)) {
            $column = $table->hasColumn('children');
        }
        if (empty($column)) {
            $table->addTextColumn('children', true, null)->update();
        }
        $table = $this->table('wiki_page_history');
        if (!empty($table)) {
            $column = $table->hasColumn('children');
        }
        if (empty($column)) {
            $table->addTextColumn('children', true, null)->update();
        }
    }

    public function down()
    {
        $table = $this->table('wiki_page');
        if (!empty($table)) {
            $table->removeColumn('children')->update();
        }
        $table = $this->table('wiki_page_history');
        if (!empty($table)) {
            $table->removeColumn('children')->update();
        }
    }
}
