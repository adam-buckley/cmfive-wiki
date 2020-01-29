<?php

class WikiPageHistory extends WikiPage
{
    // remove the searchable aspect which was defined
    // in the parent class
    public $_remove_searchable;

    public $wiki_page_id;

    public function update($force_null_values = false, $force_validation = false)
    {
        DbObject::update();
    }

    public function insert($force_validation = false)
    {
        DbObject::insert();
    }

    public function delete($force = false)
    {
        DbObject::delete($force);
    }

    public function getDbTableName()
    {
        return "wiki_page_history";
    }
}
