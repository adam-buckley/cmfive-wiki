<?php 
$table = array();
if ($hist){
		$table[]=array("Date", "User", "Action");
		foreach($hist as $ph) {
			$table[]=array(
				$ph->getDateTime("dt_created","d/m/Y H:i"),
				$w->Auth->getUser($ph->creator_id)->getFullName(),
				Html::ab(WEBROOT."/wiki/viewhistoryversion/".$wiki->name."/".$wh['name']."/".$ph->id,"View",true),
			);
		}
		echo Html::table($table,"history","tablesorter",true);
} else {
		echo "No changes yet.";
}
?>
