<?php

/**
 * EGStats - Extreme Games Statistic Aggregate Project
 * @date 2014-02-01
 * @version 0.1
 * Created by Alexander Gailey-White
 */

require_once 'EGStats/Game.php';
require_once 'EGStats/Squad.php';
require_once 'EGStats/Player.php';
require_once 'EGStats/GamePlayer.php';

class EGStats {
	const Warbird = 1;
	const Javelin = 2;
	const Spider = 3;
	const Leviathan = 4;
	const Terrier = 5;
	const Weasel = 6;
	const Lancaster = 7;
	const Shark = 8;
	const Spectator = 9;

	const Spectator_Frequency = 9999;

	/**
	 * 
	 */
	
	static function _get( $name = null, $id = null ) {
		if( !is_string( $name ) or !is_int( $id ) )
			return;
		$class = 'EGStats_' . $name;
		$object = new $class( $id );
		
		if( $object and $object->isValid() )
			return $object;
		return false;
	}
	
	/**
	 * 
	 */
	
	static function getGame( $id = null ) {
		return self::_get( 'Game', $id );
	}
	
	/**
	 * 
	 */
	
	static function getPlayer( $id = null ) {
		return self::_get( 'Player', $id );
	}
	
	/**
	 * 
	 */
	
	static function getPlayerByName( $name = null ) {
		if( !is_null( $name ) ) {
			$db = Db::getInstance();
			if( $db ) {
				$db->query(
					"SELECT `id`
					FROM `#_player`
					WHERE `name` = ?",
					$name
				);
				if( $db->numRows() )
					return self::getPlayer( (int)$db->getField() );
			}
		}
		return;
	}
	
	/**
	 * 
	 */
	
	static function getSquad( $id = null ) {
		return self::_get( 'Squad', $id );
	}

	/**
	 *
	 */

	static function getSquadByName( $name = null ) {
		if( !is_null( $name ) ) {
			$db = Db::getInstance();
			if( $db ) {
				$db->query(
					"SELECT `id`
					FROM `#_squad`
					WHERE `name` = ?",
					$name
				);
				if( $db->numRows() )
					return self::getSquad( (int)$db->getField() );
			}
		}
		return;
	}

	/**
	 * 
	 */
	
	static function getGamePlayer( $data = null ) {
		return new EGStats_GamePlayer( $data );
	}
	
	/**
	 * 
	 */
	
	static function isPublicFreq( $number = null ) {
		return ( (int)$number > 99 ? false : true );
	}
	
	/**
	 * 
	 */
	
	static function getTimeframe( $seconds = 0, $separator = null ) {
		if( is_null( $separator ) )
			$separator = ', ';
		
		$string = '';
		$seconds -= ( ( $week = floor( $seconds / 604800 ) ) * 604800 );
		$seconds -= ( ( $day = floor( $seconds / 86400 ) ) * 86400 );
		$seconds -= ( ( $hour = floor( $seconds / 3600 ) ) * 3600 );
		$seconds -= ( ( $minute = floor( $seconds / 60 ) ) * 60 );
		
		if( $week > 0 )
			$string .= $week . 'w' . $separator;
		if( $day > 0 )
			$string .= $day . 'd' . $separator;
		if( $hour > 0 )
			$string .= $hour . 'h' . $separator;
		if( $minute > 0 )
			$string .= $minute . 'm' . $separator;
		if( !strlen( $string ) OR $seconds > 0 )
			$string .= $seconds . 's';
		
		return $string;
	}
}

/**
 * 
 */

class EGStats_Object {
	private $id = 0;
	var $db = null;
	private $record = array();
	private $valid = false;
	
	/**
	 * 
	 */

	function __construct( $data = null, $table = null ) {
		if( !is_null( $table ) and is_int( $data ) ) {
			$this->id = (int)$data;
			$this->table = $table;
			
			$this->db = Db::getInstance();
			if( $this->db ) {
				$this->db->query(
					"SELECT *
					FROM `#_" . $this->table . "`
					WHERE `id` = ?",
					$this->id
				);
				if( $this->db->numRows() ) {
					$this->record = $this->db->getRow();
					unset( $this->record['id'] );
					$this->valid = true;
				}
			}
		}
		
		elseif( is_array( $data ) ) {
			$this->record = $data;
			$this->valid = true;
		}
	}

	/**
	 * 
	 */

	public function isValid() {
		return $this->valid;
	}

	/**
	 *
	 */

	public function _get( $name = null ) {
		if( array_key_exists( $name, $this->record ) )
			return $this->record[ $name ];
		return;
	}

	/**
	 *
	 */

	public function _getRecord() {
		return $this->record;
	}

	/**
	 * 
	 */

	public function getId() {
		return $this->id;
	}
}

/**
 *
 */

define( 'PATH_CACHE', dirname( __FILE__ ) . '/.cache/' );

class EGStats_Cache {
	const Age = 1800;

	static function get( $key = null ) {
		if( !is_null( $key ) ) {
			$path_cache = PATH_CACHE . $key . '.json';
			if( file_exists( $path_cache ) and ( time() - filectime( $path_cache ) ) < self::Age )
				return file_get_contents( $path_cache );
		}
		return;
	}

	static function set( $key = null, $data = null ) {
		if( !is_null( $key ) and !is_null( $data ) ) {
			$fp = fopen( PATH_CACHE . $key . '.json', 'w' );
			if( $fp ) {
				fwrite( $fp, $data );
				fclose( $fp );
				return true;
			}
			return false;
		}
		return;
	}
}