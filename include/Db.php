<?php

class Db {
	static $instance = null;
	var $parameters = array();
	var $connected = null;
	var $link = false;
	var $database = false;
	var $statement = null;
	var $resource = false;
	var $debug = array(
		'mode' => 0,
		'queries' => array()
	);
	var $count = 0;

	/**
	 * @name Function
	 */

	function __construct() {
	}

	/**
	 * @name Function
	 */

	static function getInstance( $key = null ) {
		if( is_null( self::$instance ) )
			self::$instance = new Db();
		return self::$instance;
	}
	
	/**
	 * @name Function
	 */
	
	public function isConnected() {
		return ( $this->connected ? true : false );
	}

	/**
	 * @name Function
	 */

	public function connect( $parameters = array() ) {
		$this->parameters = array_merge( array(
			'host' => 'localhost',
			'username' => 'root',
			'password' => '',
			'database' => 'mysql',
			'table_prefix' => '',
			'port' => 3306
		), $parameters );
		
		try {
			$dsn = $this->parameters['driver'] . ':host=' . $this->parameters['host'] . ';dbname=' . $this->parameters['database'];
			$this->link = new PDO( $dsn, $this->parameters['username'], $this->parameters['password'] );
			$this->link->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$this->connected = ( $this->link ? true : false );
			if( $this->connected )
				$this->query( "SET NAMES utf8" );
		} catch ( PDOException $e ) {}
	}

	/**
	 * @name Function
	 */
	
	public function escape( $string ) {
		return preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '', $string );
	}
	
	/**
	 * @name Function
	 */
	
	public function sql( /*polymorphic*/ ) {
		if( !func_num_args() )
			return;

		$values = func_get_args();
		$query = str_replace( '#_', $this->parameters['table_prefix'], array_shift( $values ) );

		if( !count( $values ) )
			return $query;
		else {
			$db =& $this->link;
			return preg_replace_callback(
				'#\\?#',
				function( $match ) use ( $db, &$values ) {
					if( empty( $values ) )
						throw new PDOException('not enough values for query');
					$value  = array_shift( $values );

					// Handle special cases: do not quote numbers, booleans, or NULL.
					if( is_null( $value ) ) return 'NULL';
					if( true === $value ) return 'true';
					if( false === $value ) return 'false';
					if( is_numeric( $value ) ) return $value;

					// Handle default case with $db charset
					return $db->quote( $value );
				},
				$query
			);
		}
	}

	/**
	 * @name Function
	 */

	public function query( /*polymorphic*/ ) {
		if( !$this->isConnected() )
			return false;
		
		$args = func_get_args();
		if( count( $args ) and $args[0] ) {
			
			// New query
			if( is_string( $args[0] ) ) {
				$this->statement = $this->link->prepare( str_replace( '#_', $this->parameters['table_prefix'], array_shift( $args ) ) );

				// Execute query
				try {
					$this->statement->execute( $args );
				} catch ( PDOException $e ) {
					var_dump($e);exit;
				}
			}
			
			// Existing query statement
			elseif( $args[0] instanceof PDOStatement )
				$this->statement = $args[0];
			
			// Error
			if( !is_object( $this->statement ) ) {
				var_dump($this->statement);exit;
				//return;
			}
		}
		
		return $this->statement;
	}
	
	/**
	 * @name Function
	 */
	
	public function insert( $table, $data = array() ) {
		if( !is_array( $data ) OR count( $data ) == 0 )
			return;
		$fields = $values = '';
		
		// Build fields and values
		foreach( $data AS $name => $value ) {
			$fields .= "`" . $name . "`, ";
			$values .= ( is_numeric( $value ) ? $value . ", " : "'" . $this->escape( $value ) . "', " );
		}
		
		$this->query(
			"INSERT INTO `" . $this->parameters['table_prefix'] . $table . "` 
			( " . preg_replace( '/, $/', '', $fields ) . " ) 
			VALUES( " . preg_replace( '/, $/', '', $values ) . " )"
		);
	}
	
	/**
	 * @name Function
	 */
	
	public function update( $table, $data = array(), $sql = null ) {
		if( !is_array( $data ) OR count( $data ) == 0 )
			return;
		
		$fields = $values = $where = '';
		
		// Build fields
		foreach( $data AS $name => $value )
			$values .= "`" . $name . "` = " . ( is_numeric( $value ) ? $value . ", " : "'" . $this->escape( $value ) . "', " );
		
		if( is_array( $sql ) ) {
			if( count( $sql ) ) {
				foreach( $sql AS $name => $value )
					$where .= "`" . $name . "` = " . ( is_numeric( $value ) ? $value . " AND " : "'" . $this->escape( $value ) . "' AND " );
				
				$where = "WHERE " . preg_replace( '/ AND $/', '', $where );
			}
		} else
			$where =& $sql;
		
		$this->query(
			"UPDATE `" . $this->parameters['table_prefix'] . $table . "` 
			SET " . preg_replace( '/, $/', '', $values ) . " 
			" . $where );
	}
	
	/**
	 * @name Function
	 */
	
	public function delete( $table, $data = array() ) {
		if( !is_array( $data ) OR count( $data ) == 0 )
			return;
		
		$fields = $values = '';
		
		// Build fields
		foreach( $data AS $name => $value )
			$staements[] = "`" . $name . "` = " . ( is_numeric( $value ) ? $value : "'" . $this->escape( $value ) . "'" );
		
		$this->query(
			"DELETE FROM `" . $this->parameters['table_prefix'] . $table . "` 
			WHERE ( " . implode( ' AND ', $staements )  ." )" );
		
		if( !$this->affectedRows() )
			return false;
		else
			return $this->affectedRows();
	}
	
	/**
	 * @name Function
	 */
	
	public function getRow( /*polymorphic*/ ) {
		if( !$this->isConnected() )
			return false;
		
		call_user_func_array( array( $this, 'query' ), func_get_args() );
		return $this->statement->fetch( PDO::FETCH_ASSOC );
	}
	
	/**
	 * @name Function
	 */
	
	public function getRows( /*polymorphic*/ ) {
		if( !$this->isConnected() )
			return false;
		
		call_user_func_array( array( $this, 'query' ), func_get_args() );
		return $this->statement->fetchAll( PDO::FETCH_ASSOC );
	}
	
	/**
	 * @name Function
	 */
	
	public function numRows( /*polymorphic*/ ) {
		if( !$this->isConnected() )
			return false;
		
		call_user_func_array( array( $this, 'query' ), func_get_args() );
		return $this->statement->rowCount();
	}
	
	/**
	 * @name Function
	 */
	
	public function affectedRows() {
		if( !$this->isConnected() )
			return false;
		
		call_user_func_array( array( $this, 'query' ), func_get_args() );
		return $this->statement->rowCount();
	}
	
	/**
	 * @name Function
	 */
	
	public function getField( $index = 0 ) {
		if( !$this->isConnected() )
			return false;
		
		call_user_func_array( array( $this, 'query' ), func_get_args() );
		$row = $this->statement->fetch( PDO::FETCH_NUM );
		return $row[ $index ];
	}
	
	/**
	 * @name Function
	 */
	
	public function getInsertId( /*polymorphic*/ ) {
		if( !$this->isConnected() )
			return false;
		
		call_user_func_array( array( $this, 'query' ), func_get_args() );
		return $this->link->lastInsertId();
	}
}