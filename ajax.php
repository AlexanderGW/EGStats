<?php

/**
 * EGStats - Extreme Games Statistic Aggregate Project
 * @date 2014-02-01
 * @version 0.1
 * Created by Alexander Gailey-White
 */

error_reporting( E_ALL &~ E_NOTICE );

require 'include/config.php';
require 'include/Db.php';
require 'include/EGStats.php';

function sanitise( $value ) {
	return trim( preg_replace( '/[\x00-\x1F\x80-\x9F\$]/u', '', $value ) );
}

$data = null;
$action = sanitise( $_GET['action'] );

if( !is_array( $_GET['value'] ) )
	$_GET['value'] = (array)$_GET['value'];
$value = array_map( 'sanitise', $_GET['value'] );
$value = array_map( 'urldecode', $value );

$key = md5( $action . ( count( $value ) ? '_' . implode( '_', $value ) : '' ) );
$data = EGStats_Cache::get( $key );

// No cache - ask the database
if( is_null( $data ) ) {
	$db = Db::getInstance();
	$db->connect( $config );

	switch( $action ) {
		case 'getSummaries' : {
			if( !$value[0] )
				$value[0] = 20;

			$db->query(
				"SELECT end_time
				FROM reset
				ORDER BY id DESC
				LIMIT ?",
				(int)$value[0]
			);

			$dates = array();
			while( $row = $db->getRow() )
				$dates[] = array( 'summary' => date( 'Y-m-d H:i:s', strtotime( $row['end_time'] ) ) );

			$data = $dates;

			break;
		}

		case 'getSummary' :
			$end = null;
			if( $value[0] ) {
				$db->query(
					"SELECT end_time
					FROM reset
					WHERE DATE( end_time ) = ?",
					$value[0]
				);

				if( $db->numRows() )
					$end = $db->getField();
			}

			if( !$end )
				$end = date( 'Y-m-d H:i:s' );

			$db->query(
				"SELECT end_time
				FROM reset
				WHERE end_time < ?
				ORDER BY id DESC
				LIMIT 1",
				$end
			);

			$start = $db->getField();

			$games_query = $db->query(
				"SELECT id
				FROM flag_games
				WHERE end_time BETWEEN ? AND ?
				ORDER BY jackpot DESC
				LIMIT 10",
				$start,
				$end
			);

			if( $db->numRows( $games_query ) ) {
				$player_points_query = $db->query(
					"SELECT p.name AS player_name, s.name AS squad_name, SUM( IF( fs.freq = fg.winning_freq, ( fg.jackpot + fs.flag_points + fs.kill_points + fs.total_kothpoints ), ( fs.flag_points + fs.kill_points + fs.total_kothpoints ) ) ) AS points
					FROM flag_games AS fg
					JOIN flag_stats AS fs ON ( fs.game_id = fg.id )
					JOIN player AS p ON ( p.id = fs.player_id )
					JOIN squad AS s ON ( s.id = fs.squad_id )
					WHERE fg.end_time BETWEEN ? AND ?
					GROUP BY fs.player_id
					ORDER BY points DESC
					LIMIT 10",
					$start,
					$end
				);

				$player_wlr_query = $db->query(//, MAX( fs.ratings ) AS ratingsjj
					"SELECT p.name AS player_name, s.name AS squad_name, SUM( fs.wins ) AS wins, SUM( fs.losses ) AS losses
					FROM flag_games AS fg
					JOIN flag_stats AS fs ON ( fs.game_id = fg.id )
					JOIN player AS p ON ( p.id = fs.player_id )
					JOIN squad AS s ON ( s.id = fs.squad_id )
					WHERE fg.end_time BETWEEN ? AND ?
					GROUP BY fs.player_id
					ORDER BY ratings DESC
					LIMIT 10",
					$start,
					$end
				);

				$squad_points_query = $db->query(
					"SELECT s.name AS squad_name, SUM( IF( fs.freq = fg.winning_freq, ( fg.jackpot + fs.flag_points + fs.kill_points + fs.total_kothpoints ), ( fs.flag_points + fs.kill_points + fs.total_kothpoints ) ) ) AS points
					FROM flag_games AS fg
					JOIN flag_stats AS fs ON ( fs.game_id = fg.id )
					JOIN squad AS s ON ( s.id = fs.squad_id )
					WHERE s.name != '' AND fg.end_time BETWEEN ? AND ?
					GROUP BY fs.squad_id
					ORDER BY points DESC
					LIMIT 10",
					$start,
					$end
				);

				$squad_wlr_query = $db->query(//, SUM( fs.ratings ) AS ratings
					"SELECT s.name AS squad_name, SUM( fs.wins ) AS wins, SUM( fs.losses ) AS losses
				FROM flag_games AS fg
				JOIN flag_stats AS fs ON ( fs.game_id = fg.id )
				JOIN player AS p ON ( p.id = fs.player_id )
				JOIN squad AS s ON ( s.id = fs.squad_id )
				WHERE s.name != '' AND fg.end_time BETWEEN ? AND ?
				GROUP BY fs.squad_id
				ORDER BY ratings DESC
				LIMIT 10",
					$start,
					$end
				);

				$data = array(
					'start' => date( 'Y-m-d', strtotime( $start ) ),
					'end' => date( 'Y-m-d', strtotime( $end ) ),
					'games' => array(),
					'players' => array(),
					'squads' => array()
				);

				$games = $db->getRows( $games_query );
				foreach( $games as $game )
					$data['games'][] = EGStats::getGame( (int)$game[ 'id' ] )->getArray();

				$data['players'] = array(
					'points' => $db->getRows( $player_points_query ),
					'ratio' => $db->getRows( $player_wlr_query )
				);

				$data['squads'] = array(
					'points' => $db->getRows( $squad_points_query ),
					'ratio' => $db->getRows( $squad_wlr_query )
				);
			}

			break;

		// Games

		case 'getGamesByDate' :

			// Day
			if( preg_match( '/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $value[0], $matches ) ) {
				$start = $end = $matches[ 0 ];
			}

			// Week
			elseif( preg_match( '/([0-9]{4})-([0-9]{2})/', $value[0], $matches ) ) {
				$date = new DateTime();
				$date->setISODate( $matches[ 1 ], $matches[ 2 ] );
				$start = $date->format( 'Y-m-d' );
				$end = date( 'Y-m-d', strtotime( $start ) + 86400 * 7 );

				/*$games_query = $db->query(
					"SELECT id
					FROM flag_games
					WHERE end_time BETWEEN ? AND ?
					ORDER BY id DESC",
					$start . ' 00:00:00',
					$end . ' 23:59:59'
				);

				$player_points_query = $db->query(
					"SELECT p.name AS player_name, s.name AS squad_name, SUM( IF( fs.freq = fg.winning_freq, ( fg.jackpot + fs.flag_points + fs.kill_points + fs.total_kothpoints ), ( fs.flag_points + fs.kill_points + fs.total_kothpoints ) ) ) AS points
					FROM flag_games AS fg
					JOIN flag_stats AS fs ON ( fs.game_id = fg.id )
					JOIN player AS p ON ( p.id = fs.player_id )
					JOIN squad AS s ON ( s.id = fs.squad_id )
					WHERE fg.end_time BETWEEN ? AND ?
					GROUP BY fs.player_id
					ORDER BY points DESC
					LIMIT 10",
					$start . ' 00:00:00',
					$end . ' 23:59:59'
				);

				$player_wlr_query = $db->query(
					"SELECT p.name AS player_name, s.name AS squad_name, SUM( fs.wins ) AS wins, SUM( fs.losses ) AS losses, MAX( fs.ratings ) AS ratings
					FROM flag_games AS fg
					JOIN flag_stats AS fs ON ( fs.game_id = fg.id )
					JOIN player AS p ON ( p.id = fs.player_id )
					JOIN squad AS s ON ( s.id = fs.squad_id )
					WHERE fg.end_time BETWEEN ? AND ?
					GROUP BY fs.player_id
					ORDER BY ratings DESC
					LIMIT 10",
					$start . ' 00:00:00',
					$end . ' 23:59:59'
				);*/
			}

			// Fallback to today
			else
				$start = $end = date( 'Y-m-d' );

			//if( !$games_query ) {
				$games_query = $db->query(
					"SELECT id
					FROM flag_games
					WHERE end_time BETWEEN ? AND ?
					ORDER BY id DESC",
					$start . ' 00:00:00',
					$end . ' 23:59:59'
				);

				$player_points_query = $db->query(
					"SELECT p.name AS player_name, s.name AS squad_name, SUM( IF( fs.freq = fg.winning_freq, ( fg.jackpot + fs.flag_points + fs.kill_points + fs.total_kothpoints ), ( fs.flag_points + fs.kill_points + fs.total_kothpoints ) ) ) AS points
					FROM flag_games AS fg
					JOIN flag_stats AS fs ON ( fs.game_id = fg.id )
					JOIN player AS p ON ( p.id = fs.player_id )
					JOIN squad AS s ON ( s.id = fs.squad_id )
					WHERE fg.end_time BETWEEN ? AND ?
					GROUP BY fs.player_id
					ORDER BY points DESC
					LIMIT 10",
					$start . ' 00:00:00',
					$end . ' 23:59:59'
				);

				$player_wlr_query = $db->query(
					"SELECT p.name AS player_name, s.name AS squad_name, SUM( fs.wins ) AS wins, SUM( fs.losses ) AS losses, MAX( fs.ratings ) AS ratings
					FROM flag_games AS fg
					JOIN flag_stats AS fs ON ( fs.game_id = fg.id )
					JOIN player AS p ON ( p.id = fs.player_id )
					JOIN squad AS s ON ( s.id = fs.squad_id )
					WHERE fg.end_time BETWEEN ? AND ?
					GROUP BY fs.player_id
					ORDER BY ratings DESC
					LIMIT 10",
					$start . ' 00:00:00',
					$end . ' 23:59:59'
				);
			//}

			if( $db->numRows( $games_query ) ) {
				$data = array(
					'start' => $start,
					'end' => $end,
					'games' => array(),
					'players' => array()
				);

				$games = $db->getRows( $games_query );
				foreach( $games as $game )
					$data['games'][] = EGStats::getGame( (int)$game[ 'id' ] )->getArray();

				$data['players'] = array(
					'points' => $db->getRows( $player_points_query ),
					'ratio' => $db->getRows( $player_wlr_query )
				);
			}
			break;

		case 'getGame' :
			$game = EGStats::getGame( (int)$value[0] );
			if( $game ) {
				$data = array(
					'game' => $game->getArray(),
					'players' => array(
						'winners' => array(),
						'losers' => array(),
						'participants' => array(),
						'mvp' => $game->getMVP()
					)
				);

				$players = $game->getWinningPlayers();
				foreach( $players as $player )
					$data['players']['winners'][] = $player->getArray();

				$players = $game->getLosingPlayers();
				foreach( $players as $player )
					$data['players']['losers'][] = $player->getArray();

				$players = $game->getParticipatingPlayers();
				foreach( $players as $player )
					$data['players']['participants'][] = $player->getArray();
			}
			break;

		case 'getGameWinners' :
			$game = EGStats::getGame( (int)$value[0] );
			if( $game ) {
				$data = array();
				$players = $game->getWinningPlayers();
				foreach( $players as $player )
					$data[] = $player->getArray();
			}
			break;

		case 'getGameLosers' :
			$game = EGStats::getGame( (int)$value[0] );
			if( $game ) {
				$data = array();
				$players = $game->getLosingPlayers();
				foreach( $players as $player )
					$data[] = $player->getArray();
			}
			break;

		case 'getGameParticipants' :
			$game = EGStats::getGame( (int)$value[0] );
			if( $game ) {
				$data = array();
				$players = $game->getParticipatingPlayers();
				foreach( $players as $player )
					$data[] = $player->getArray();
			}
			break;

		case 'getGameMvp' :
			$game = EGStats::getGame( (int)$value[0] );
			if( $game ) {
				$data = $game->getMVP();
			}
			break;

		// Squad

		case 'getSquad' :
			$squad_value =& $value[0];
			$date_stamp =& $value[1];

			$squad = ( is_numeric( $squad_value ) ? EGStats::getSquad( (int)$squad_value ) : EGStats::getSquadByName( $squad_value ) );

			if( $squad ) {
				if( !$date_stamp )
					$date_stamp = date( 'Y-W' );

				// Day
				if( strlen( $date_stamp ) == 10 )
					$start = $end = $date_stamp;

				// Week
				else {
					$date = new DateTime();
					list( $year, $week ) = explode( '-', $date_stamp );
					$date->setISODate( $year, $week );
					$start = $date->format( 'Y-m-d' );
					$end = date( 'Y-m-d', strtotime( $start ) + 86400 * 7 );

					$player_points_query = $db->query(
						"SELECT p.name AS player_name, s.name AS squad_name, SUM( IF( fs.freq = fg.winning_freq, ( fg.jackpot + fs.flag_points + fs.kill_points + fs.total_kothpoints ), ( fs.flag_points + fs.kill_points + fs.total_kothpoints ) ) ) AS points
						FROM flag_games AS fg
						JOIN flag_stats AS fs ON ( fs.game_id = fg.id )
						JOIN player AS p ON ( p.id = fs.player_id )
						JOIN squad AS s ON ( s.id = fs.squad_id )
						WHERE fg.end_time BETWEEN ? AND ? AND fs.squad_id = ?
						GROUP BY fs.player_id
						ORDER BY points DESC
						LIMIT 10",
						$start . ' 00:00:00',
						$end . ' 23:59:59',
						$squad->getId()
					);

					$player_wlr_query = $db->query(
						"SELECT p.name AS player_name, s.name AS squad_name, SUM( fs.wins ) AS wins, SUM( fs.losses ) AS losses, MAX( fs.ratings ) AS ratings
						FROM flag_games AS fg
						JOIN flag_stats AS fs ON ( fs.game_id = fg.id )
						JOIN player AS p ON ( p.id = fs.player_id )
						JOIN squad AS s ON ( s.id = fs.squad_id )
						WHERE fg.end_time BETWEEN ? AND ? AND fs.squad_id = ?
						GROUP BY fs.player_id
						ORDER BY ratings DESC
						LIMIT 10",
						$start . ' 00:00:00',
						$end . ' 23:59:59',
						$squad->getId()
					);
				}

				$data = array(
					'start' => $start,
					'end' => $end,
					'squad' => $squad->getArray(),
					'players' => array(
						'points' => array(),
						'ratio' => array()
					)
				);

				if( $db->numRows( $player_points_query ) )
					$data['players']['points'] = $db->getRows( $player_points_query );

				if( $db->numRows( $player_wlr_query ) )
					$data['players']['ratio'] = $db->getRows( $player_wlr_query );
			}
			break;

		// Players

		case 'getPlayer' :
			$player_value =& $value[0];
			$date_stamp =& $value[1];

			$player = ( is_numeric( $player_value ) ? EGStats::getPlayer( (int)$player_value ) : EGStats::getPlayerByName( $player_value ) );

			if( $player ) {
				$data = array(
					'start' => $start,
					'end' => $end,
					'player' => $player->getArray(),
					'games' => array()
				);

				if( $date_stamp ) {

					// Day
					if( strlen( $date_stamp ) == 10 ) {
						$start = $end = $date_stamp;

						$games_query = $db->query(
							"SELECT DISTINCT fg.id
							FROM flag_stats AS fs
							JOIN flag_games AS fg
							ON ( fg.id = fs.game_id )
							WHERE fs.player_id = ? AND ( fg.end_time BETWEEN ? AND ? )
							ORDER BY fg.start_time",
							$player->getId(),
							$end . ' 00:00:00',
							$end . ' 23:59:59'
						);
					}

					// Week
					else {
						$date = new DateTime();
						list( $year, $week ) = explode( '-', $date_stamp );
						$date->setISODate( $year, $week );
						$start = $date->format( 'Y-m-d' );
						$end = date( 'Y-m-d', strtotime( $start ) + 86400 * 7 );

						$games_query = $db->query(
							"SELECT DISTINCT fg.id
							FROM flag_stats AS fs
							JOIN flag_games AS fg
							ON ( fg.id = fs.game_id )
							WHERE fs.player_id = ? AND ( fg.end_time BETWEEN ? AND ? )
							ORDER BY fg.start_time",
							$player->getId(),
							$start . ' 00:00:00',
							$end . ' 23:59:59'
						);
					}

					$data['start'] = $start;
					$data['end'] = $end;
				} else
					$games_query = $db->query(
						"SELECT fg.id
						FROM flag_stats AS fs
						JOIN flag_games AS fg
						ON ( fg.id = fs.game_id )
						WHERE fs.player_id = ?
						ORDER BY fg.start_time DESC
						LIMIT 20",
						$player->getId()
					);

				if( $db->numRows() ) {
					$rows = $db->getRows();
					foreach( $rows as $row ) {
						$game = EGStats::getGame( (int)$row[ 'id' ] );
						switch( $game->getPlayerRole( $player->getId() ) ) {
							case EGStats_GamePlayer::Role_Participated :
								$data[ 'games' ][ 'participated' ][] = $game->getArray();
								break;

							case EGStats_GamePlayer::Role_Lost :
								$data[ 'games' ][ 'lost' ][] = $game->getArray();
								break;

							case EGStats_GamePlayer::Role_Won :
								$data[ 'games' ][ 'won' ][] = $game->getArray();
								break;
						}
					}
				}
			}

			break;

		case 'getPlayerByName' :
			$player = EGStats::getPlayerByName( $value[0] );
			if( $player )
				$data = $player->getArray();
			break;
	}

	if( !is_null( $data ) ) {
		header( 'Content-type: application/json' );
		EGStats_Cache::set( $key, ( $data = json_encode( $data ) ) );
	}
}

die( $data );