<?php

/**
 * 
 */

class EGStats_Squad extends EGStats_Object {
	function __construct( $id = null ) {
		parent::__construct( $id, 'squad' );
	}

	/**
	 *
	 */

	public function getArray() {
		$items = $this->_getRecord();
		return $items;
	}

	/**
	 * 
	 */
	
	public function getName() {
		return $this->_get( 'name' );
	}
	
	/**
	 * 
	 */
	
	public function getDate() {
		return $this->_get( 'date' );
	}
}

?>