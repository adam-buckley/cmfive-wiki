<?php

class WikiAddChildrenColumn extends CmfiveMigration {

	public function up() {
		$table = $this->table('wiki');
		if (!empty($table)) {
			$column = $table->hasColumn('children');
		}
		if (empty($column)) {
			$table->addTextColumn('children',true,null)->update();
		}
	}

	public function down() {
		$table = $this->table('wiki');
		if (!empty($table)) {
			$table->removeColumn('children')->update();
		}
		
	}

}
