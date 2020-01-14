<?php
class WikiUser extends DbObject
{
    public $wiki_id;
    public $user_id;
    public $role;

    public function getUser()
    {
        return $this->Auth->getUser($this->user_id);
    }

    public function getFullName()
    {
        return $this->getUser()->getFullName();
    }

    public function getDbTableName()
    {
        return "wiki_user";
    }
}
