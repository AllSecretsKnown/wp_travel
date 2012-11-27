<?php

require_once( 'interface/itravlr.php' );

class Travlr implements iTravlr{


	//Prefixes used in APC cache
	CONST GOES_AROUND = 'GOES_AROUND';
	CONST COMES_AROUND = 'COMES_AROUND';
	CONST STATIONS = 'STATIONS';

	//Querys for stations, arrivals and departures
	CONST STATIONS_QUERY = 'stations.json';

	//Private members
	private $auth = 'tagtider:codemocracy';
	private $api_url = 'http://api.tagtider.net/v1/';

	//Will hold all available stations from api.tagtider.net
	private $stations;

	//Time to cache response from api.tagtider.net
	private $travlr_ttl;

	function __construct($ttl = ''){
		if(isset($ttl) && $ttl !== ''){
			$int_val = intval($ttl);
			if($int_val > 0){
				$this->travlr_ttl = $int_val;
			}

		}
		else{
			$this->travlr_ttl = 60 * 60;
		}

		$this->stations = $this->_get_stations();
	}

	/*
	 * Public static function to get Info about all incoming traffic
	 * @param string - Station name
	 * @return array(time => origin)
	 */
	public function what_comes_around($station){
		if(!empty($station)){
			$arrivals = $this->_process_request($station, Travlr::COMES_AROUND);
		}else{
			return array();
		}

		$return_objects = array();

		if(count($arrivals) > 0){
			if(is_array($arrivals) && isset($arrivals['Error'])){
				return $arrivals;
			}
			foreach ( $arrivals->transfer as $incoming ) {
				$return_objects[$incoming->arrival] = $incoming->origin;
			}
		}

		return $return_objects;
	}

	/*
	 * Public static function to get info about all outgoing traffic
	 * @param string - Station name
	 * @return array(time => destination)
	 */
	public function what_goes_around($station){
		if(!empty($station)){
			$departures = $this->_process_request($station, Travlr::GOES_AROUND);
		}else{
			return array();
		}

		$return_objects = array();

		if(count($departures) > 0){
			if(is_array($departures) && isset($departures['Error'])){
				return $departures;
			}
			foreach ( $departures->transfer as $outgoing ) {
				$return_objects[$outgoing->departure] = $outgoing->destination;
			}
		}

		return $return_objects;
	}

	/*
	 * Private function to handle request
	 * @params string Station, string Constant defined in class, describing if we are coming or going
	 * @return array with objects
	 */
	private function _process_request($station, $coming_or_going){
		$station_prefix = trim(substr(strtolower($station), 0, 4));
		$id = $this->_get_station_id($station);
		$object = array();
		$json_result = false;

		if($id == false){
			return null;
		}

		switch ($coming_or_going) {
			case Travlr::GOES_AROUND:
				$query = 'stations/' . $id . '/transfers/departures.json';
				$json_result = apc_fetch(Travlr::GOES_AROUND . $station_prefix);
				break;
			case Travlr::COMES_AROUND:
				$query = 'stations/' . $id . '/transfers/arrivals.json';
				$json_result = apc_fetch(Travlr::COMES_AROUND . $station_prefix);
				break;
		}
		if($json_result == false){
			$json_result = $this->_setup_and_execute_curl($query);
		}

		if($json_result != null){
			if($coming_or_going == Travlr::COMES_AROUND){
				apc_store(Travlr::COMES_AROUND .$station_prefix, $json_result, $this->travlr_ttl);
			}else{
				apc_store(Travlr::GOES_AROUND .$station_prefix, $json_result, $this->travlr_ttl);
			}
			$object = json_decode($json_result);
		}elseif($json_result === null){
			return array('Error' => 'Could not connect to remote API');
		}
		return $object->station->transfers;
	}

	/*
	 * Private Function to get all stations available
	 * @param -
	 * @return stdClass with id
	 */
	private function _get_stations(){
		$stations = array();
		//Check if we have stations-json in cache
		if(!$stations_json = apc_fetch(Travlr::STATIONS)){
			$stations_json = $this->_setup_and_execute_curl(Travlr::STATIONS_QUERY);
		}

		if($stations_json !== null){
			apc_store(Travlr::STATIONS, $stations_json, $this->travlr_ttl * 24);
			$stations = json_decode($stations_json);
		}else{
			return $stations;
		}
		return $stations->stations->station;
	}

	/*
	 * Private Function to get ID for requested Station
	 * @param string - station name
	 * @return station ID
	 */
	private function _get_station_id($station){
		foreach ( $this->stations as $known_station ) {
			if(strtolower($known_station->name) == strtolower($station)){
				return $known_station->id;
			}
		}
		return null;
	}

	/*
	 * Private function to execute curl request to api.tagtider.net
	 * @param string query
	 * @return response json/xml
	 */
	private function _setup_and_execute_curl($query){

		$full_url = $this->api_url;

		if(!isset($query)){
			return null;
		}else{
			$full_url .= $query;
		}

		try{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($ch, CURLOPT_USERPWD, $this->auth);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept' => 'application/json'));
			curl_setopt($ch, CURLOPT_URL, $full_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$response = curl_exec($ch);
			curl_getinfo($ch);
			$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
		}catch(Exception $e){
			exit('Could not connect to remote API : ' . $e);
		}

		if($http_status == "200"){
			return $response;
		}else{
			return null;
		}
	}
}