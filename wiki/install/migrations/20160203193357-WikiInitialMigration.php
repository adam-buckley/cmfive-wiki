<?php

class WikiInitialMigration extends CmfiveMigration
{
    public function up()
    {
        $column = parent::Column();
        $column->setName('id')
            ->setType('biginteger')
            ->setIdentity(true);

        /**
         * wiki TABLE
         */
        if (!$this->hasTable('wiki')) {
            $this->table('wiki', [
                'id' => false,
                'primary_key' => 'id'
            ])->addColumn($column)
                ->addColumn('title', 'string', ['limit' => 255])
                ->addColumn('name', 'string', ['limit' => 255])
                ->addColumn('owner_id', 'integer')
                ->addColumn('is_public', 'integer')
                ->addColumn('last_modified_page_id', 'integer')
                ->addCmfiveParameters()
                ->create();
        }

        /**
         * wiki page TABLE
         */
        if (!$this->hasTable('wiki_page')) {
            $this->table('wiki_page', [
                'id' => false,
                'primary_key' => 'id'
            ])->addColumn($column)
                ->addColumn('name', 'string', ['limit' => 255])
                ->addColumn('wiki_id', 'integer')
                ->addColumn('body', 'text', ['null' => true])
                ->addCmfiveParameters()
                ->create();
        }

        /**
         * wiki page history TABLE
         */
        if (!$this->hasTable('wiki_page_history')) {
            $this->table('wiki_page_history', [
                'id' => false,
                'primary_key' => 'id'
            ])->addColumn($column)
                ->addColumn('wiki_page_id', 'integer')
                ->addColumn('name', 'string', ['limit' => 255])
                ->addColumn('wiki_id', 'integer')
                ->addColumn('body', 'text', ['null' => true])
                ->addCmfiveParameters()
                ->create();
        }

        /**
         * wiki user TABLE
         */
        if (!$this->hasTable('wiki_user')) {
            $this->table('wiki_user', [
                'id' => false,
                'primary_key' => 'id'
            ])->addColumn($column)
                ->addColumn('wiki_id', 'integer')
                ->addColumn('user_id', 'integer')
                ->addColumn('role', 'string', ['limit' => 20])
                ->create();
        }
    }

    public function down()
    {
        $this->hasTable('wiki') ? $this->dropTable('wiki') : null;
        $this->hasTable('wiki_page') ? $this->dropTable('wiki_page') : null;
        $this->hasTable('wiki_page_history') ? $this->dropTable('wiki_page_history') : null;
        $this->hasTable('wiki_user') ? $this->dropTable('wiki_user') : null;
    }
}
