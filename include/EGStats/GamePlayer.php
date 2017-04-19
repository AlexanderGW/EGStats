<?php

/**
 * 
 */

class EGStats_GamePlayer extends EGStats_Object {
	const Role_Participated = 0;
	const Role_Lost = 1;
	const Role_Won = 2;

	function __construct( $data = null ) {
		parent::__construct( $data );
	}

	/**
	 *
	 */

	public function getArray() {
		$items = $this->_getRecord();
		foreach( $items as $item => $value ) {
			switch( $item ) {
				case 'freq' :
					if( !EGStats::isPublicFreq( ( $items[ $item ] = (int)$items[ $item ] ) ) )
						$items[ $item ] = md5( '_' . $items[ $item ] );
					break;
				case 'ratings' :
					$items[ $item ] = (float)$value;
					break;
				case 'player_name' :
				case 'squad_name' :
					break;
				default :
					$items[ $item ] = (int)$value;
			}
		}

		unset( $items['here'] );
		return $items;
	}

	/**
	 * 
	 */
	
	public function getGameId() {
		return $this->_get( 'game_id' );
	}
	
	/**
	 * 
	 */
	
	public function getPlayerId() {
		return $this->_get( 'player_id' );
	}
	
	/**
	 * 
	 */
	
	public function getSquadId() {
		return $this->_get( 'squad_id' );
	}
	
	/**
	 * 
	 */
	
	public function getFlagPoints() {
		return $this->_get( 'flag_points' );
	}
	
	/**
	 * 
	 */
	
	public function getKillPoints() {
		return $this->_get( 'kill_points' );
	}
	
	/**
	 * 
	 */
	
	public function getTotalPoints() {
		return $this->getFlagPoints() + $this->getKillPoints() + $this->getKothPoints();
	}
	
	/**
	 * 
	 */
	
	public function getWins() {
		return $this->_get( 'wins' );
	}
	
	/**
	 * 
	 */
	
	public function getLosses() {
		return $this->_get( 'losses' );
	}
	
	/**
	 * 
	 */
	
	public function getTeamkills() {
		return $this->_get( 'teamkills' );
	}
	
	/**
	 * 
	 */
	
	public function getGoals() {
		return $this->_get( 'goals' );
	}
	
	/**
	 * 
	 */
	
	public function getRating() {
		return $this->_get( 'rating' );
	}
	
	/**
	 * 
	 */
	
	public function getFlagsLost() {
		return $this->_get( 'flags_lost' );
	}
	
	/**
	 * 
	 */
	
	public function getFlagsGained() {
		return $this->_get( 'flags_killed' );
	}
	
	/**
	 * 
	 */
	
	public function getFlagPickups() {
		return $this->_get( 'flags_pickedup' );
	}
	
	/**
	 * 
	 */
	
	public function getFlagsDropped() {
		return $this->_get( 'flags_dropped' );
	}
	
	/**
	 * 
	 */
	
	public function getFlagTime() {
		return $this->_get( 'flag_time' );
	}
	
	/**
	 * 
	 */
	
	public function getDamageTaken() {
		return $this->_get( 'damage_taken' );
	}
	
	/**
	 * 
	 */
	
	public function getDamageDelt() {
		return $this->_get( 'damage_given' );
	}
	
	/**
	 * 
	 */
	
	public function getAttaches() {
		return $this->_get( 'attaches' );
	}
	
	/**
	 * 
	 */
	
	public function getAttachedTo() {
		return $this->_get( 'attached_to' );
	}
	
	/**
	 * 
	 */
	
	public function getTotalGreens() {
		return $this->_get( 'total_greens' );
	}
	
	/**
	 * 
	 */
	
	public function getKothCount() {
		return $this->_get( 'total_koths' );
	}
	
	/**
	 * 
	 */
	
	public function getKothPoints() {
		return $this->_get( 'total_kothpoints' );
	}
	
	/**
	 * 
	 */
	
	public function getTimeWarbird() {
		return $this->_get( 'warbird_time' );
	}
	
	/**
	 * 
	 */
	
	public function getTimeJavelin() {
		return $this->_get( 'javelin_time' );
	}
	
	/**
	 * 
	 */
	
	public function getTimeSpider() {
		return $this->_get( 'spider_time' );
	}
	
	/**
	 * 
	 */
	
	public function getTimeLeviathan() {
		return $this->_get( 'leviathan_time' );
	}
	
	/**
	 * 
	 */
	
	public function getTimeTerrier() {
		return $this->_get( 'terrier_time' );
	}
	
	/**
	 * 
	 */
	
	public function getTimeWeasel() {
		return $this->_get( 'weasal_time' ); // Spelling error on table
	}
	
	/**
	 * 
	 */
	
	public function getTimeLancaster() {
		return $this->_get( 'lancaster_time' );
	}
	
	/**
	 * 
	 */
	
	public function getTimeShark() {
		return $this->_get( 'shark_time' );
	}
	
	/**
	 * 
	 */
	
	public function getTimeSpectator() {
		return $this->_get( 'spectator_time' );
	}
	
	/**
	 * 
	 */
	
	public function getFavoriteShip() {
		$ships = array(
			$this->getTimeWarbird(),
			$this->getTimeJavelin(),
			$this->getTimeSpider(),
			$this->getTimeLeviathan(),
			$this->getTimeTerrier(),
			$this->getTimeWeasel(),
			$this->getTimeLancaster(),
			$this->getTimeShark()
		);
		
		$highest = max( $ships );
		if( !$highest )
			return;
		return ( 1 + array_search( $highest, $ships ) );
	}
	
	/**
	 * 
	 */
	
	public function getFavoriteShipName() {
		$ship = $this->getFavoriteShip();
		switch( $ship ) {
			case EGStats::Warbird :
				return 'Warbird';
			case EGStats::Javelin :
				return 'Javelin';
			case EGStats::Spider :
				return 'Spider';
			case EGStats::Leviathan :
				return 'Leviathan';
			case EGStats::Terrier :
				return 'Terrier';
			case EGStats::Weasel :
				return 'Weasel';
			case EGStats::Lancaster :
				return 'Lancaster';
			case EGStats::Shark :
				return 'Shark';
		}
		return;
	}
	
	/**
	 * 
	 */
	
	public function getFreq() {
		return $this->_get( 'freq' );
	}
	
	/**
	 * 
	 */
	
	public function sawGameFinish() {
		return ( $this->_get( 'here' ) ? true : false );
	}
	
	/**
	 * 
	 */
	
	public function getName() {
		return $this->_get( 'player_name' );
	}
	
	/**
	 * 
	 */
	
	public function getSquad() {
		return $this->_get( 'squad_name' );
	}
}