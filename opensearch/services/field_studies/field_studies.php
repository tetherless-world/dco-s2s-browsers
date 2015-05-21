<?php

include_once("../../../s2s/opensearch/utils.php");

// parent class S2SConfig
include_once("../../../s2s/opensearch/config.php");

class DCO_FieldStudies_S2SConfig extends S2SConfig {
	
	private $namespaces = array(
		'dco'	=> "http://info.deepcarbon.net/schema#",
		'vivo'	=> "http://vivoweb.org/ontology/core#",
		'vitro'	=> "http://vitro.mannlib.cornell.edu/ns/vitro/0.7#",
		'bibo'	=> "http://purl.org/ontology/bibo/",
		'foaf'	=> "http://xmlns.com/foaf/0.1/",
		'rdfs'	=> "http://www.w3.org/2000/01/rdf-schema#",
		'time'	=> "http://www.w3.org/2006/time#",
		'xsd'	=> "http://www.w3.org/2001/XMLSchema#",
		'skos'	=> "http://www.w3.org/2004/02/skos/core#",
		'owl'	=> "http://www.w3.org/2002/07/owl#",
		'dct'	=> "http://purl.org/dc/terms/",
		'dc'	=> "http://purl.org/dc/elements/1.1/",
		'obo'	=> "http://purl.obolibrary.org/obo/",
		'dcat'	=> "http://www.w3.org/ns/dcat#",
		'vitro-public' => "http://vitro.mannlib.cornell.edu/ns/vitro/public#"
	);
	
	/**
	* Return SPARQL endpoint URL
	* @return string SPARQL endpoint URL
	*/
	public function getEndpoint() {
		return "http://fuseki:3030/vivo/query";
	}

	/**
	* Return array of prefix, namespace key-value pairs
	* @return array of prefix, namespace key-value pairs
	*/
	public function getNamespaces() {
		return $this->namespaces;
	}
	
	/**
	* Execute SPARQL select query
	* @param string $query SPARQL query to execute
	* @return array an array of associative arrays containing the bindings of the query results
	*/
	public function sparqlSelect($query) {
	
		$options = array(
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 120
		);
				
		$encoded_query = 'query=' . urlencode($query) . '&output=xml';
		return execSelect($this->getEndpoint(), $encoded_query, $options);
	}

	/**
      * Get participants for a given field study
      * @param string $field_study field study uri
	  * @param string $role label of participants'role 
      * @return array an array of associative arrays containing the participant bindings
      */
	private function getParticipantsByFieldStudy($field_study, $role) {
		$query = $this->getPrefixes();
		$query .= "SELECT DISTINCT ?uri ?name WHERE { ";
		$query .= "<$field_study> vivo:realizedRole ?role . ";
		$query .= "{?role rdfs:label \"$role\"^^xsd:string . } UNION ";
		$query .= "{?role rdfs:label \"$role\" . } ";
		$query .= "?role vivo:researcherRoleOf ?uri . ";
		$query .= "?uri rdfs:label ?n . ";
		$query .= "BIND(str(?n) AS ?name) } ";
		return $this->sparqlSelect($query);
	}

	/**
      * Get leaders for a given field study
      * @param string $field_study field study uri
      * @return array an array of associative arrays containing the leader bindings
      */
	private function getLeadersByFieldStudy($field_study) {
		$query = $this->getPrefixes();
		$query .= "SELECT DISTINCT ?uri ?label WHERE { ";
		$query .= "<$field_study> dco:fieldworkLeader ?uri . ";
		$query .= "?uri rdfs:label ?l . ";
		$query .= "BIND(str(?l) AS ?label) } ";
		return $this->sparqlSelect($query);
	}

	/**
      * Get field sties for a given field study
      * @param string $field_study field study uri
      * @return array an array of associative arrays containing the field site bindings
      */
	private function getFieldSitesByFieldStudy($field_study) {
		$query = $this->getPrefixes();
		$query .= "SELECT DISTINCT ?uri ?label WHERE { ";
		$query .= "<$field_study> dco:hasPhysicalLocation ?uri . ";
		$query .= "?uri rdfs:label ?l . ";
		$query .= "BIND(str(?l) AS ?label) } ";
		return $this->sparqlSelect($query);
	}

	/**
      * Get infomation for a given field site
      * @param string $field_site field site uri
      * @return array an array of associated array containing the field site information bindings
      */
	private function getFieldSiteInfo($field_site) {
		$query = $this->getPrefixes();
		$query .= "SELECT DISTINCT ?latitude ?longitude ?altitude ?depth ?pressure ?temperature WHERE { ";
		$query .= "<$field_site> dco:hasLatitude ?lat . ";
		$query .= "<$field_site> dco:hasLongitude ?long . ";
		$query .= "OPTIONAL{ <$field_site> dco:altitude ?alt . } ";
		$query .= "OPTIONAL{ <$field_site> dco:depth ?dp . } ";
		$query .= "OPTIONAL{ <$field_site> dco:pressure ?ps . } ";
		$query .= "OPTIONAL{ <$field_site> dco:temperature ?temp . } ";
		$query .= "BIND(str(?lat) AS ?latitude) . ";
		$query .= "BIND(str(?long) AS ?longitude) . "; 
		$query .= "BIND(str(?alt) AS ?altitude) . ";
		$query .= "BIND(str(?dp) AS ?depth) . ";
		$query .= "BIND(str(?ps) AS ?pressure) . ";
		$query .= "BIND(str(?temp) AS ?temperature) } ";
		return $this->sparqlSelect($query);
	}

	/**
	* Return count of total search results for specified constraints
	* @param array $constraints array of arrays with search constraints
	* @result int search result count
	*/
	public function getSearchResultCount(array $constraints) {
		
		$query = $this->getSelectQuery("count", $constraints);
		$results = $this->sparqlSelect($query);
		$result = $results[0];
		return $result['count'];
	}

	/**
      * Return JSON representation of the date facet content
      * @param array $results array of associative arrays with bindings from query execution
      * @return string JSON representation of date facet content
      */
    public function getDateFacetOutput(array $results) {

        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json");

        if($this->setCacheControlHeader()) {
            header($this->getFacetCacheControlHeader());
        }

        foreach ($results as $i => $result) {
            $results[$i]['date'] = substr($result['date'], 0, 10);
        }

        return json_encode($results);
    }

	/**
	* Create HTML of search result
	* @param array $result query result to be processed into HTML
	* @return string HTML div of search result entry
	*/
	public function getSearchResultOutput(array $result) {

		$html = "<div class='result-list-item'>";
		
		// label
		$field_study_summary_url = "http://deepcarbon.net/dco_field_study_summary?uri=" . $result['field_study'];
		$html .= "<span class='title'>";
		$html .= "<a target='_blank' href=\"" . $field_study_summary_url . "\">" . $result['label'] . "</a>";
		$html .= "</span>";

		// DCO-ID
		if (isset($result['dco_id'])) {
			$dco_id_label = substr(@$result['dco_id'], 25);
			$html .= "<br /><span>DCO ID: <a target='_blank' href=\"" . $result['dco_id'] . "\">" . $dco_id_label . "</a></span>";
		}

		// Investigators
		$investigators = $this->getParticipantsByFieldStudy($result['field_study'], "Field Study Investigator");
		if (count($investigators) > 0) {
			$html .= "<br /><span>Investigators: ";
			$investigators_markup = array();
			foreach ($investigators as $i => $investigator) {
				$vivo_url = $VIVO_URL_PREFIX . substr($investigator['uri'], strripos($investigator['uri'], '/')); 
				array_push($investigators_markup, "<a target='_blank' href=\"" . $vivo_url . "\">" . $investigator['name'] . "</a>");
			}
			$html .= implode('; ', $investigators_markup);
			$html .= "</span>";
		}

		// Research Team Members
		$members = $this->getParticipantsByFieldStudy($result['field_study'], "Team Member");
		if (count($members) > 0) {
			$html .= "<br /><span>Team Members: ";
			$members_markup = array();
			foreach ($members as $i => $member) {
				$vivo_url = $VIVO_URL_PREFIX . substr($member['uri'], strripos($member['uri'], '/'));
				array_push($members_markup, "<a target='_blank' href=\"" . $vivo_url . "\">" . $member['name'] . "</a>");
			}
			$html .= implode('; ', $members_markup);
			$html .= "</span>";
		}

		// communities
		if (isset($result['community'])) {
			$html .= "<br /><span>Communities: ";
			$comm_arr = explode(",", $result['community']);
			$comm_label_arr = explode(",", $result['community_label']);
			$communities_markup = array();
			foreach ($comm_arr as $i => $comm) {
				array_push($communities_markup, "<a target='_blank' href=\"" . $comm . "\">" . $comm_label_arr[$i] . "</a>");
			}
			$html .= implode('; ', $communities_markup);
			$html .= "</span>";
		}

		// groups
		if (isset($result['group'])) {
			$html .= "<br /><span>Groups: ";
			$group_arr = explode(",", $result['group']);
			$group_label_arr = explode(",", $result['group_label']);
			$groups_markup = array();
			foreach ($group_arr as $i => $group) {
				array_push($groups_markup, "<a target='_blank' href=\"" . $group . "\">" . $group_label_arr[$i] . "</a>");
			}
			$html .= implode('; ', $groups_markup);
			$html .= "</span>";
		}

		// Grants 
		if (isset($result['grant'])) {
			$html .= "<br /><span>Grants: ";
			$grant_arr = explode(",", $result['grant']);
			$grant_label_arr = explode(",", $result['grant_label']);
			$grants_markup = array();
			foreach ($grant_arr as $i => $grant) {
				$grant_summary_url = "http://deepcarbon.net/dco_grant_summary?uri=" . $grant_arr[$i];
                            	array_push($grants_markup, "<a target='_blank' href=\"" . $grant_summary_url . "\">" . $grant_label_arr[$i] . "</a>");
			}
			$html .= implode('; ', $grants_markup);
			$html .= "</span>";
		}

		$html .= "</div>";
		return $html;
	}

	/**
	  * Get json result for field study map service
	  * @param $results array of associative arrays with bindings from query execution
	  * @return string JSON representation of the map data
	  */
	public function getMapResultsOutput(array $results) {

		header("Access-Control-Allow-Origin: *");
		header("Content-Type: application/json");
				
		if($this->setCacheControlHeader()) {
			header($this->getSearchResultCacheControlHeader());
		}

		$new_results = array();

		foreach ($results as $i => $result) {
			$new_result = array();

			// URI
			$new_result['uri'] = $result['field_study'];

			// label
			$new_result['label'] = "<p>Field study: <a href=\"http://deepcarbon.net/dco_field_study_summary?uri=" . $result['field_study'] . "\" target=\"_blank\">" . $result['label'] . "</a></p>";

			// DCO-ID
			$new_result['dco_id'] = "<p>DCO ID: <a href=\"" . $result['dco_id'] . "\" target=\"_blank\">" . substr($result['dco_id'], 25) . "</a></p>";

			// open to journalists
			$new_result['open_to_journalists'] = "<p></p>";
			if (isset($result['open_to_journalists'])) {
				$new_result['open_to_journalists'] = "<p>Open to journalists: " . $result['open_to_journalists'] . "</p>";
			}

			// thumbnail
			$new_result['thumbnail'] = "<div class=\"popup-thumbnail\"></div>";
			if (isset($result['thumbnail'])) {
				$new_result['thumbnail'] = "<div class=\"popup-thumbnail\"><img src=\"" . $result['thumbnail'] . "\" alt=\"" . $result['label'] . "\"></div>";
			}

			// leaders
			$new_result['leaders'] = "<p></p>";
			$leaders = $this->getLeadersByFieldStudy($result['field_study']);
			if (count($leaders) > 0) {
				$leaders_html = array();
				foreach ($leaders as $i => $l) {
					$html = "<a href=\"" . $l['uri'] . "\" target=\"_blank\">" . $l['label'] . "</a>";
					array_push($leaders_html, $html);
				}
				$new_result['leaders'] = "<p>Leaders: " . implode("; ", $leaders_html) . "</p>";
			}

			// communities
			$new_result['communities'] = "<p></p>";
			if (count($result['community']) > 0) {
				$comm_arr = explode(",", $result['community']);
                        	$comm_label_arr = explode(",", $result['community_label']);
				$communities_html = array();
				foreach ($comm_arr as $i => $c) {
					$html = "<a href=\"". $c . "\" target=\"_blank\">" . $comm_label_arr[$i] . "</a>";
					array_push($communities_html, $html);
				}
				$new_result['communities'] = "<p>Communities: " . implode("; ", $communities_html) . "</p>";
			}

			// groups
			$new_result['groups'] = "<p></p>";
			if (count($result['group']) > 0) {
				$gp_arr = explode(",", $result['group']);
                        	$gp_label_arr = explode(",", $result['group_label']);
				$groups_html = array();
				foreach ($gp_arr as $i => $g) {
					$html = "<a href=\"" . $g . "\" target=\"_blank\">" . $gp_label_arr[$i] . "</a>";
					array_push($groups_html, $html);
				}
				$new_result['groups'] = "<p>Groups: " . implode("; ", $groups_html) . "</p>";
			}

			// field sites
			$field_sites = $this->getFieldSitesByFieldStudy($result['field_study']);
			$new_result['field_sites'] = array();
			if (count($field_sites) > 0) {
				foreach($field_sites as $i => $fs) {
					$field_site = array();
					$field_site['label'] = "<p>Field site: <a href=\"" . $fs['uri'] . "\" target=\"_blank\">" . $fs['label'] . "</a></p>";
					$fs_info = $this->getFieldSiteInfo($fs['uri']);
					if (count($fs_info) > 0) {
						$field_site['latitude'] = preg_replace("/[^-.\d]/", "", $fs_info[0]['latitude']);
						$field_site['longitude'] = preg_replace("/[^-.\d]/", "", $fs_info[0]['longitude']);
						$field_site['altitude'] = "<p></p>";
						if (isset($fs_info[0]['altitude'])) {
							$field_site['altitude'] = "<p>Altitude: " . strip_tags($fs_info[0]['altitude']) . "</p>";
						}
						$field_site['depth'] = "<p></p>";
						if (isset($fs_info[0]['depth'])) {
							$field_site['depth'] = "<p>Depth: " . strip_tags($fs_info[0]['depth']) . "</p>";
						}
						$field_site['pressure'] = "<p></p>";
						if (isset($fs_info[0]['pressure'])) {
							$field_site['pressure'] = "<p>Pressure: " . strip_tags($fs_info[0]['pressure']) . "</p>";
						}
						$field_site['temperature'] = "<p></p>";
						if (isset($fs_info[0]['temperature'])) {
							$field_site['temperature'] = "<p>Temperature: " . strip_tags($fs_info[0]['temperature']) . "</p>";
						}
					}
					array_push($new_result['field_sites'], $field_site);
				}
			}	
			array_push($new_results, $new_result);
		}
		return json_encode($new_results);
	}

	/**
	* Return SPARQL query header component
	* @param string $type search type (e.g. 'datasets', 'authors', 'keywords')
	* @return string query header component (e.g. 'SELECT ?id ?label')
	*/
	public function getQueryHeader($type) {
	
		$header = "";
		switch($type) {
			case "field_studies":
			case "field_studies_map":
			case "field_studies_open_to_journalists_map":
				$header .= "?field_study ?dco_id ?label ?thumbnail ?open_to_journalists ";
				$header .= '(GROUP_CONCAT(DISTINCT ?comm ; SEPARATOR=",") AS ?community) ';
				$header .= '(GROUP_CONCAT(DISTINCT ?comm_label ; SEPARATOR=",") AS ?community_label) ';
				$header .= '(GROUP_CONCAT(DISTINCT ?gp ; SEPARATOR=",") AS ?group) ';
                $header .= '(GROUP_CONCAT(DISTINCT ?gp_label ; SEPARATOR=",") AS ?group_label) ';
                $header .= '(GROUP_CONCAT(DISTINCT ?gt ; SEPARATOR=",") AS ?grant) ';
				$header .= '(GROUP_CONCAT(DISTINCT ?gt_label ; SEPARATOR=",") AS ?grant_label) ';
				break;
			case "startDate":
				$header .= '?date';
				break;
			case "endDate":
				$header .= '?date';
				break;
			case "count":
				$header .= "(count(DISTINCT ?field_study) AS ?count)";
				break;
			default:
				$header .= "?id ?label (COUNT(DISTINCT ?field_study) AS ?count)";
				break;
		}
		return $header;
	}
	
	/**
	* Return SPARQL query footer component
	* @param string $type search type (e.g. 'datasets', 'authors', 'keywords')
	* @param int $limit size of result set
	* @param int $offset offset into result set
	* @param string $sort query result sort parameter
	* @return string query footer component (e.g. 'GROUP BY ?label ?id')
	*/
	public function getQueryFooter($type, $limit=null, $offset=0, $sort=null) {
	
		$footer = "";
		switch($type) {
			case "field_studies":
			case "field_studies_map":
			case "field_studies_open_to_journalists_map":
				$footer .= " GROUP BY ?field_study ?dco_id ?label ?open_to_journalists ?thumbnail";
				$footer .= " ORDER BY ?label";
				if ($type == 'field_studies') 
					if ($limit)	$footer .= " LIMIT $limit OFFSET $offset";
				break;
			case "count":
			case "startDate":
			case "endDate":
				break;
			default:
				$footer .= " GROUP BY ?label ?id";
				break;
		}
		return $footer;
	}
	
	/**
	  * Return SPARQL query WHERE clause minus constraint clauses for specified search type
	  * @param string $type search type (e.g. 'datasets', 'authors', 'keywords')
	  * @return string WHERE clause component minus constraint clauses (e.g. '?dataset a dcat:Dataset . ')
	  */
	public function getQueryBody($type) {
		
		$body = "";
		switch($type) {
			case "communities":
				$body .= "?field_study a dco:FieldStudy . ";
				$body .= "?field_study  dco:associatedDCOCommunity ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;
				
			case "groups":
				$body .= "?field_study a dco:FieldStudy . ";
				$body .= "?field_study dco:associatedDCOPortalGroup ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;

			case "grants":
				$body .= "?field_study a dco:FieldStudy . ";
				$body .= "?field_study vivo:hasFundingVehicle ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
			break;

			case "participants":
				$body .= "?field_study a dco:FieldStudy . ";
				$body .= "{?field_study obo:BFO_0000055 ?role . } UNION ";
				$body .= "{?field_study vivo:contributingRole ?role . } ";
				$body .= "?role obo:RO_0000052 ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
			break;

			case "startDate":
				$body .= "?field_study a dco:FieldStudy ; vivo:dateTimeInterval [vivo:start [vivo:dateTime ?d]] . ";
				$body .= "BIND(str(?d) AS ?date) . ";
				break; 

			case "endDate":
				$body .= "?field_study a dco:FieldStudy ; vivo:dateTimeInterval [vivo:end [vivo:dateTime ?d]] . ";
				$body .= "BIND(str(?d) AS ?date) . ";
				break;

            case "reportingYears":
                $body .= "?field_study a dco:FieldStudy . " ;
                $body .= "?field_study dco:hasProjectUpdate ?projectUpdate . " ;
                $body .= "?projectUpdate dco:forReportingYear ?id . ";
                $body .= "?id rdfs:label ?l . " ;
                $body .= "BIND(str(?l) AS ?label) . ";
                break;
				
			case "count":
				$body .= "?field_study a dco:FieldStudy . ";
				break;
				
			case "field_studies":
			case "field_studies_map":
				$body .= "?field_study a dco:FieldStudy . ";
				$body .= "?field_study rdfs:label ?l . ";
				$body .= "OPTIONAL { ?field_study dco:hasDcoId ?id . } ";
				$body .= "OPTIONAL { ?field_study dco:associatedDCOCommunity ?comm . ?comm rdfs:label ?c_l . } ";
				$body .= "OPTIONAL { ?field_study dco:associatedDCOPortalGroup ?gp . ?gp rdfs:label ?g_l . } ";
				$body .= "OPTIONAL { ?field_study vivo:hasFundingVehicle ?gt . ?gt rdfs:label ?gt_l . } ";
				$body .= "OPTIONAL { ?field_study vivo:description ?desc . } ";
				$body .= "OPTIONAL { ?field_study dco:openToJournalists ?open . } ";
				$body .= "OPTIONAL { ?field_study vitro-public:mainImage [vitro-public:thumbnailImage [vitro-public:downloadLocation ?thumbnail]] . } ";
				$body .= "BIND(str(?l) AS ?label) . ";
				$body .= "BIND(str(?id) AS ?dco_id) . ";
				$body .= "BIND(str(?desc) AS ?description) . ";
				$body .= "BIND(str(?c_l) AS ?comm_label) . ";
				$body .= "BIND(str(?g_l) AS ?gp_label) . ";
				$body .= "BIND(str(?gt_l) AS ?gt_label) . ";
				$body .= "BIND(str(?open) AS ?open_to_journalists) . ";
				break;
			case "field_studies_open_to_journalists_map":
				$body .= "?field_study a dco:FieldStudy . ";
				$body .= "?field_study rdfs:label ?l . ";
				$body .= '?field_study dco:openToJournalists "Yes"^^xsd:string . '; 
				$body .= "OPTIONAL { ?field_study dco:hasDcoId ?id . } ";
				$body .= "OPTIONAL { ?field_study dco:associatedDCOCommunity ?comm . ?comm rdfs:label ?c_l . } ";
				$body .= "OPTIONAL { ?field_study dco:associatedDCOPortalGroup ?gp . ?gp rdfs:label ?g_l . } ";
				$body .= "OPTIONAL { ?field_study vivo:hasFundingVehicle ?gt . ?gt rdfs:label ?gt_l . } ";
				$body .= "OPTIONAL { ?field_study vivo:description ?desc . } ";
				$body .= "OPTIONAL { ?field_study vitro-public:mainImage [vitro-public:thumbnailImage [vitro-public:downloadLocation ?thumbnail]] . } ";
				$body .= "BIND(str(?l) AS ?label) . ";
				$body .= "BIND(str(?id) AS ?dco_id) . ";
				$body .= "BIND(str(?desc) AS ?description) . ";
				$body .= "BIND(str(?c_l) AS ?comm_label) . ";
				$body .= "BIND(str(?g_l) AS ?gp_label) . ";
				$body .= "BIND(str(?gt_l) AS ?gt_label) . ";
				$body .= "BIND(str(?open) AS ?open_to_journalists) . ";
				break;
		}
				
		return $body;
	}

	/**
	* Return constraints component of SPARQL query
	* @param array $constraints array of arrays with search constraints
	* @return string constraints component of SPARQL query
	*/
	public function getQueryConstraints(array $constraints) {
		
		$body = "";		
		foreach($constraints as $constraint_type => $constraint_values) {
			if ($constraint_type == "startDate") {
				$body .= "?field_study vivo:dateTimeInterval [vivo:start [vivo:dateTime ?startDate]] . FILTER (?startDate >= \"" . $constraint_values . "T00:00:00\"^^xsd:dateTime) . ";
			}
			else if ($constraint_type == "endDate") {
				$body .= "?field_study vivo:dateTimeInterval [vivo:end [vivo:dateTime ?endDate]] . FILTER (?endDate <= \"" . $constraint_values . "T00:00:00\"^^xsd:dateTime) . ";
			}
			else {		
				$arr = array();	
				foreach($constraint_values as $i => $constraint_value) {
					$constraint_clause = $this->getQueryConstraint($constraint_type, $constraint_value);
					array_push($arr, $constraint_clause);
				}
				$body .= implode(' UNION ', $arr) . ' ';
			}
		}
		return $body;
	}
	
	/**
	* Return constraint clause to be included in SPARQL query
	* @param string $constraint_type constraint type (e.g. 'keywords')
	* @param string $constraint_value constraint value (e.g. 'Toxic')
	* @return string constraint clause to be included in SPARQL query
	*/	
	public function getQueryConstraint($constraint_type, $constraint_value) {
		
		$body = "";
		switch($constraint_type) {
			case "communities":
				$body .= "{ ?field_study dco:associatedDCOCommunity <$constraint_value> }";
				break;
			case "groups":
				$body .= "{ ?field_study dco:associatedDCOPortalGroup <$constraint_value> }";
				break;
			case "grants":
				$body .= "{ ?field_study vivo:hasFundingVehicle <$constraint_value> }";
				break;
			case "participants":
				$body .= "{{ ?field_study obo:BFO_0000055 ?role . } UNION { ?field_study vivo:contributingRole ?role . } ?role obo:RO_0000052 <$constraint_value> . }";
				break;
            case "reportingYears":
                $body .= "{ ?field_study dco:hasProjectUpdate ?projectUpdate . ?projectUpdate dco:forReportingYear <$constraint_value> }";
                break;
			default:
				break;
		}
		return $body;
	}
	
    /**
     * For each selection in a facet add a link to the context for that selection
     *
     * using the individual link for the different types as the context
     * for the selection
     *
     * @param array $results selections to add context to
	 * @param string $type search type (e.g. 'datasets', 'authors', 'keywords')
     */
	private function addContextLinks(&$results, $type) {
		
		if ($type == "communities"
            || $type == "groups"
            || $type == "participants"
            || $type == "reportingYears") {
			foreach ( $results as $i => $result ) {
				$results[$i]['context'] = $result['id']; 
			}
		}
	}
	
	/**
	* Return representation (HTML or JSON) of response to send to client
	* @param array $results array of associative arrays with bindings from query execution
	* @param string $type search type (e.g. 'datasets', 'authors', 'keywords')
	* @param array $constraints array of arrays with search constraints
	* @param int $limit size of result set
	* @param int $offset offset into result set
	* @return string representation of response to client
	*/
	public function getOutput(array $results, $type, array $constraints, $limit=0, $offset=0) {
	
		// Output for the request type "field_studies"			
		if ($type == "field_studies") {
			$count = $this->getSearchResultCount($constraints);						
			return $this->getSearchResultsOutput($results, $limit, $offset, $count);
		}
		// Output for the request type "field_studies_map" 
		else if ($type == "field_studies_map" || $type == "field_studies_open_to_journalists_map") {
			return $this->getMapResultsOutput($results);
		}
		// Output for for the reuqest type "startDate" or "endDate"
		else if ($type == 'startDate' || $type == 'endDate') {
			return $this->getDateFacetOutput($results);
		} 
		// Output for other types of requests (i.e. other search facets)
		else {		
			$this->addContextLinks($results, $type);
			return $this->getFacetOutput($results);
		}
	}
}
