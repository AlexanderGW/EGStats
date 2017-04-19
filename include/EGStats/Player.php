<?php

/**
 * 
 */

class EGStats_Player extends EGStats_Object {
	function __construct( $id = null ) {
		parent::__construct( $id, 'player' );
	}

	/**
	 *
	 */

	public function getArray() {
		$items = $this->_getRecord();
		$items['player_id'] = $this->getId();
		$items['squad_name'] = ( $squad = $this->getSquad() ) ? $squad->getName() : null;
		unset( $items['last_msg_id'], $items['date'], $items['scorereset'] );
		return $items;
	}

	/**
	 *
	 */

	public function getSquadId() {
		$players_sql = $this->db->query(
			"SELECT squad_id
			FROM flag_stats
			WHERE player_id = ?
			ORDER BY game_id DESC
			LIMIT 1",
			$this->getId()
		);
		if( $this->db->numRows() )
			return (int)$this->db->getField();
		return 1;
	}
	
	/**
	 * 
	 */
	
	public function getSquad() {
		if( $this->getSquadId() )
			return EGStats::getSquad( $this->getSquadId() );
		return;
	}
	
	/**
	 * 
	 */
	
	public function getName() {
		return $this->_get( 'name' );
	}
}

?>