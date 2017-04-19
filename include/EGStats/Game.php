<?php

/**
 * 
 */

class EGStats_Game extends EGStats_Object {
	function __construct( $id = null ) {
		parent::__construct( $id, 'flag_games' );
	}

	/**
	 *
	 */

	public function getArray() {
		$items = array();
		$items['game_id'] = $this->getId();
		$items += $this->_getRecord();
		$items['jackpot'] = (int)$items['jackpot'];

		if( $this->isPublic() )
			$items['arena'] = (int)$items['arena'];

		if( !EGStats::isPublicFreq( ( $items['winning_freq'] = (int)$items['winning_freq'] ) ) )
			$items['winning_freq'] = null;
		$items['duration'] = $this->getDuration();
		unset( $items['zone'] );
		return $items;
	}

	/**
	 * 
	 */
	
	public function getZone() {
		return $this->_get( 'zone' );
	}
	
	/**
	 * 
	 */
	
	public function getArena() {
		return $this->_get( 'arena' );
	}
	
	/**
	 * 
	 */
	
	public function isPublic() {
		return ( is_numeric( $this->getArena() ) );
	}
	
	/**
	 * 
	 */
	
	public function getStartTime() {
		return $this->_get( 'start_time' );
	}
	
	/**
	 * 
	 */
	
	public function getEndTime() {
		return $this->_get( 'end_time' );
	}
	
	/**
	 * 
	 */
	
	public function getDuration() {
		if( $this->getEndTime() )
			return strtotime( $this->getEndTime() ) - strtotime( $this->getStartTime() );
		else
			return time() - strtotime( $this->getStartTime() );
	}
	
	/**
	 * 
	 */
	
	public function getWinningFreq() {
		return $this->_get( 'winning_freq' );
	}
	
	/**
	 * 
	 */
	
	public function getJackpot() {
		return $this->_get( 'jackpot' );
	}

	/**
	 *
	 */

	public function getPlayerRole( $id = null ) {
		if( is_numeric( $id ) ) {
			$this->db->query(
				"SELECT freq, here
				FROM flag_stats
				WHERE game_id = ? AND player_id = ?",
				$this->getId(),
				(int)$id
			);
			if( $this->db->numRows() ) {
				$row = $this->db->getRow();
				if( $row['here'] == '0' or $row['freq'] == EGStats::Spectator_Frequency )
					return EGStats_GamePlayer::Role_Participated;
				else {
					if( $this->getWinningFreq() == $row['freq'] )
						return EGStats_GamePlayer::Role_Won;
					else
						return EGStats_GamePlayer::Role_Lost;
				}
			}
		}
		return;
	}

	/**
	 * 
	 */
	
	public function getMVP() {
		$this->db->query(
			"SELECT player_id
			FROM flag_stats
			WHERE game_id = ?
			ORDER BY ratings DESC, flag_points DESC, kill_points DESC
			LIMIT 1",
			$this->getId()
		);
		if( $this->db->numRows() )
			return (int)$this->db->getField();
		return;
	}
	
	/**
	 * 
	 */
	
	public function getWinningPlayers() {
		$players = array();
		$players_sql = $this->db->query(
			"SELECT fs.*, p.name AS player_name, s.name AS squad_name
			FROM flag_stats fs
			JOIN player p
			ON ( p.id = fs.player_id )
			JOIN squad s
			ON ( s.id = fs.squad_id )
			WHERE fs.game_id = ? AND fs.freq = ? AND fs.here = 1
			ORDER BY player_name",
			$this->getId(),
			$this->getWinningFreq()
		);
		if( $this->db->numRows() ) {
			while( $row = $this->db->getRow( $players_sql ) )
				$players[] = EGStats::getGamePlayer( $row );
		}
		
		return $players;
	}
	
	/**
	 * 
	 */
	
	public function getLosingPlayers() {
		$players = array();
		$players_sql = $this->db->query(
			"SELECT fs.*, p.name AS player_name, s.name AS squad_name
			FROM flag_stats fs
			JOIN player p
			ON ( p.id = fs.player_id )
			JOIN squad s
			ON ( s.id = fs.squad_id )
			WHERE fs.game_id = ? AND fs.freq != ? AND fs.freq != ? AND fs.here = 1
			ORDER BY freq, p.name",
			$this->getId(),
			$this->getWinningFreq(),
			EGStats::Spectator_Frequency
		);
		if( $this->db->numRows() ) {
			while( $row = $this->db->getRow( $players_sql ) )
				$players[] = EGStats::getGamePlayer( $row );
		}
		
		return $players;
	}

	/**
	 *
	 */

	public function getParticipatingPlayers() {
		$players = array();
		$players_sql = $this->db->query(
			"SELECT fs.*, p.name AS player_name, s.name AS squad_name
			FROM flag_stats fs
			JOIN player p
			ON ( p.id = fs.player_id )
			JOIN squad s
			ON ( s.id = fs.squad_id )
			WHERE fs.game_id = ? AND ( fs.here = 0 OR fs.freq = ? )
			ORDER BY freq, p.name",
			$this->getId(),
			EGStats::Spectator_Frequency
		);
		if( $this->db->numRows() ) {
			while( $row = $this->db->getRow( $players_sql ) )
				$players[] = EGStats::getGamePlayer( $row );
		}

		return $players;
	}

	/**
	 *
	 */

	public function getAllPlayers() {
		return $this->getWinningPlayers() + $this->getLosingPlayers() + $this->getParticipatingPlayers();
	}
}