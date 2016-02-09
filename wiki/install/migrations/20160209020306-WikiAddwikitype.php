<?php

class WikiAddwikitype extends CmfiveMigration {

	public function up() {
		$table = $this->table('wiki');
		if (!empty($table)) {
			$column = $table->hasColumn('type');
		}
		if (empty($column)) {
			$table->addStringColumn('type',true,40,'markdown')->update();
		}
	}

	public function down() {
		$table = $this->table('wiki');
		if (!empty($table)) {
			$table->removeColumn('type')->update();
		}
		
	}

}
