<?php
use Sabre\DAV;

/**
 * INode wrapper for a Wiki
 * extending DBObjectInode to add wiki pages as children
 */
class WikiINode extends DBObjectINode {

	/**
	 * Returns an array of File and/or Directory objects.
	 */
	function getChildren() {
		$result=parent::getChildren();
		$children=$this->getDBObject()->getPages();
		if (is_array($children)) {
			foreach ($children as  $object) {
				if ($object->canList($this->w->Auth->user())) {
					$iNode=new WikiPageINode($this->w,$object);
					$result[]=$iNode;
				}
			}
		}
		return $result;
	} 
	
		/**
	 * Returns a File or Directory object for the child-node of the given name.
	 */
	function getChild($name) {
		// look for matching wiki page child
		$children=$this->getDBObject()->getPages();
		if (is_array($children)) {
			foreach ($children as  $object) {
				if ($object->canView($this->w->Auth->user())) {
					if ($object->getSelectOptionTitle()==$name) {
						$result=new WikiPageINode($this->w,$object);
					} else {
						throw new Exception ('No access to this wiki page');
					}
				}
			}
		}
		// fallback to matching attachment
		if (empty($result)) {
			$children=$this->w->File->getAttachments($this->getDBObject(),$this->getDBObject()->id);
			if (is_array($children)) {
				foreach ($children as  $object) {
					if ($object->filename==$name) {
						if ($object->canView($this->w->Auth->user())) {
							$result=new AttachmentINode($this->w,$object);
						} else {
							throw new Exception ('No access to this attachment');
						}
					}
				}
			}
			
		}
		if (empty($result)) {
			throw new Exception('No matching child '.$name);
		}
		return $result;
	}

}
