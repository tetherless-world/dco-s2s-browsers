<?php

include_once("../../../s2s/opensearch/utils.php");

// parent class S2SConfig
include_once("../../../s2s/opensearch/config.php");

class DCO_Projects_S2SConfig extends S2SConfig {

	public $VIVO_URL_PREFIX = "http://info.deepcarbon.net/vivo/individual";

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
		'dcat'	=> "http://www.w3.org/ns/dcat#"
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

    private $queryParameter = "query=" ;
    private $queryOutputParameter = "&output=xml" ; // fuseki endpoint
    //private $queryOutputParameter = "&resultFormat=RS_XML" ; // native VIVO endpoint

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
				
		$encoded_query = $this->queryParameter . urlencode($query) . $this->queryOutputParameter;
		return execSelect($this->getEndpoint(), $encoded_query, $options);
	}

	/**
        * Get participants for a given project
        * @param string $project project  uri
        * @param string $role role label
        * @return array an array of associative arrays containing the participant bindings
        */
	private function getParticipantsByProject($project, $role) {
		$query = $this->getPrefixes();
		$query .= "SELECT DISTINCT ?uri ?name WHERE { ";
		$query .= "<$project> vivo:realizedRole ?role . ";
		$query .= "{?role rdfs:label \"$role\"^^xsd:string . } UNION ";
		$query .= "{?role rdfs:label \"$role\" . } ";
		$query .= "?role vivo:researcherRoleOf ?uri . ";
		$query .= "?uri rdfs:label ?n . ";
		$query .= "BIND(str(?n) AS ?name) } ";
		return $this->sparqlSelect($query);
	}

    /*
    private function getProjectUpdatesForProjects(array $projects)
    {
        $query = $this->getPrefixes();
        $query .= "SELECT DISTINCT ?project ?projectUpdate ?projectUpdateLabel ?reportingYearLabel WHERE { ";

        foreach ($projects as $i => $project) {
            $query .= "OPTIONAL { BIND(str(<$project>) AS ?project) . <$project> dco:hasProjectUpdate ?projectUpdate . } ";
        }

        $query .= "OPTIONAL { ?projectUpdate rdfs:label ?pul . BIND(str(?pul) AS ?projectUpdateLabel) . } " ;
        $query .= "OPTIONAL { ?projectUpdate dco:forReportingYear ?reportingYear . ?reportingYear rdfs:label ?ryl . BIND(str(?ryl) AS ?reportingYearLabel) . } " ;
        $query .= " }";
        return $query;
    }
    */

    /*
    private function getProjectUpdatesByProject($project) {
        $query = $this->getPrefixes();
        $query .= "SELECT DISTINCT ?projectUpdate ?projectUpdateLabel ?reportingYearLabel
WHERE
{
  <$project> dco:hasProjectUpdate ?projectUpdate .
  OPTIONAL {
  	?projectUpdate rdfs:label ?pul .
  	BIND(str(?pul) AS ?projectUpdateLabel) .
  }
  OPTIONAL {
  	?projectUpdate dco:forReportingYear ?reportingYear .
  	?reportingYear rdfs:label ?ryl .
  	BIND(str(?ryl) AS ?reportingYearLabel) .
  }
}";
        return $this->sparqlSelect($query);
    }
    */

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
		$project_summary_url = "http://deepcarbon.net/dco_project_summary?uri=" . $result['project'];
		$html .= "<span class='title'>";
		$html .=  "<a target='_blank' href=\"" . $project_summary_url . "\">" . $result['label'] . "</a>";
		$html .= "</span>";

		// DCO-ID
		if (isset($result['dco_id'])) {
			$dco_id_label = substr(@$result['dco_id'], 25);
			$html .= "<br /><span>DCO ID: <a target='_blank' href=\"" . $result['dco_id'] . "\">" . $dco_id_label . "</a></span>";
		}

		// Investigators
		$investigators = $this->getParticipantsByProject($result['project'], "Project Investigator");
		if (count($investigators) > 0) {
			$html .= "<br /><span>Investigators: ";
			$investigators_markup = array();
			foreach ($investigators as $i => $investigator) {
				$vivo_url = $this->VIVO_URL_PREFIX . substr($investigator['uri'], strripos($investigator['uri'], '/'));
				array_push($investigators_markup, "<a target='_blank' href=\"" . $vivo_url . "\">" . $investigator['name'] . "</a>");
			}
			$html .= implode('; ', $investigators_markup);
			$html .= "</span>";
		}

		// Research Team Members
		$members = $this->getParticipantsByProject($result['project'], "Research Team Member");
		if (count($members) > 0) {
			$html .= "<br /><span>Research Team Members: ";
			$members_markup = array();
			foreach ($members as $i => $member) {
				$vivo_url = $this->VIVO_URL_PREFIX . substr($member['uri'], strripos($member['uri'], '/'));
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
			$html .= "<div>Grants: ";
			$grant_arr = explode(",", $result['grant']);
			$grant_label_arr = explode(",", $result['grant_label']);
			$grants_markup = array();
			foreach ($grant_arr as $i => $grant) {
				$grant_summary_url = "http://deepcarbon.net/dco_grant_summary?uri=" . $grant_arr[$i];
                            	array_push($grants_markup, "<a target='_blank' href=\"" . $grant_summary_url . "\">" . $grant_label_arr[$i] . "</a>");
			}
			$html .= implode('; ', $grants_markup);
			$html .= "</div>";
		}

		/*
        $html .= $this->getSearchResultProjectUpdateHTML($result);
		*/

		$html .= "</div>";
		return $html;
	}

    /**
     * Create HTML for Project Update section of search result entry
     * @param array $result
     * @return string HTML
     */
    /*
    private function getSearchResultProjectUpdateHTML(array $result) {

        $html = "";

        $project_updates = $this->getProjectUpdatesByProject($result['project']);

        if(count($project_updates) > 0) {

            $reportingYears = array();

            foreach ($project_updates as $i => $project_update) {

                $reportingYearLabel = $project_update["reportingYearLabel"];

                if(array_key_exists($reportingYearLabel, $reportingYears)) {
                    array_push($reportingYears[$reportingYearLabel], $project_update);
                } else {
                    $reportingYears[$reportingYearLabel] = array($project_update);
                }
            }

            $html .= "<div><span>Project Updates:</span>";
            $html .= "<ul>";

            // define a li for each reporting year (will become a tab)
            foreach ($reportingYears as $i => $reportingYear) {

                $html .= "<li>$i<ul>" ;

                // loop through the different updates
                foreach($reportingYear as $j => $projectUpdate) {

                    $projectUpdateURI = $projectUpdate["projectUpdate"];
                    $projectUpdateLabel = $projectUpdate["projectUpdateLabel"];
                    $projectUpdateURI_Local = "http://udco.tw.rpi.edu/vivo/" . substr($projectUpdateURI, 27);
                    $html .= "<li><a href='$projectUpdateURI_Local'>$projectUpdateLabel</a></li>" ;
                }

                $html .= "</ul></li>";

            }

            $html .= "</ul>";
            $html .= "</div>"; // #project-updates
        }

        return $html;
    }
    */

	/**
	* Return SPARQL query header component
	* @param string $type search type (e.g. 'datasets', 'authors', 'keywords')
	* @return string query header component (e.g. 'SELECT ?id ?label')
	*/
	public function getQueryHeader($type) {
	
		$header = "";
		switch($type) {
			case "projects":
				$header .= "?project ?dco_id ?label ";
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
				$header .= "(count(DISTINCT ?project) AS ?count)";
				break;
			default:
				$header .= "?id ?label (COUNT(DISTINCT ?project) AS ?count)";
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
			case "projects":
				$footer .= " GROUP BY ?project ?dco_id ?label";
				$footer .= " ORDER BY ?label";
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
				$body .= "?project a vivo:Project . ";
				$body .= "?project  dco:associatedDCOCommunity ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "FILTER(NOT EXISTS{?project a dco:FieldStudy . })";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;
				
			case "groups":
				$body .= "?project a vivo:Project . ";
				$body .= "?project dco:associatedDCOPortalGroup ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "FILTER(NOT EXISTS{?project a dco:FieldStudy . })";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;

			case "grants":
				$body .= "?project a vivo:Project . ";
				$body .= "?project vivo:hasFundingVehicle ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "FILTER(NOT EXISTS{?project a dco:FieldStudy . })";
				$body .= "BIND(str(?l) AS ?label) . ";
			break;

			case "participants":
				$body .= "?project a vivo:Project . ";
				$body .= "{?project obo:BFO_0000055 ?role . } UNION ";
				$body .= "{?project vivo:contributingRole ?role . } ";
				$body .= "?role obo:RO_0000052 ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "FILTER(NOT EXISTS{?project a dco:FieldStudy . })";
				$body .= "BIND(str(?l) AS ?label) . ";
			break;

			case "startDate":
				$body .= "?project a vivo:Project ; vivo:dateTimeInterval [vivo:start [vivo:dateTime ?d]] . ";
				$body .= "FILTER(NOT EXISTS{?project a dco:FieldStudy . })";
				$body .= "BIND(str(?d) AS ?date) . ";
				break; 

			case "endDate":
				$body .= "?project a vivo:Project ; vivo:dateTimeInterval [vivo:end [vivo:dateTime ?d]] . ";
				$body .= "FILTER(NOT EXISTS{?project a dco:FieldStudy . })";
				$body .= "BIND(str(?d) AS ?date) . ";
				break;

            case "reportingYears":
                $body .= "?project a vivo:Project . " ;
                $body .= "?project dco:hasProjectUpdate ?projectUpdate . " ;
                $body .= "?projectUpdate dco:forReportingYear ?id . ";
                $body .= "?id rdfs:label ?l . " ;
                $body .= "FILTER(NOT EXISTS{?project a dco:FieldStudy . })";
                $body .= "BIND(str(?l) AS ?label) . ";
                break;
				
			case "count":
				$body .= "?project a vivo:Project . ";
				$body .= "FILTER(NOT EXISTS{?project a dco:FieldStudy . })";
				break;
				
			case "projects":
				$body .= "?project a vivo:Project . ";
				$body .= "?project rdfs:label ?l . ";
				$body .= "OPTIONAL { ?project dco:hasDcoId ?id . } ";
				$body .= "OPTIONAL { ?project dco:associatedDCOCommunity ?comm . ?comm rdfs:label ?c_l . } ";
				$body .= "OPTIONAL { ?project dco:associatedDCOPortalGroup ?gp . ?gp rdfs:label ?g_l . } ";
				$body .= "OPTIONAL { ?project vivo:hasFundingVehicle ?gt . ?gt rdfs:label ?gt_l . } ";
				$body .= "OPTIONAL { ?project vivo:description ?desc . } ";
				$body .= "FILTER(NOT EXISTS{?project a dco:FieldStudy . })";
				$body .= "BIND(str(?l) AS ?label) . ";
				$body .= "BIND(str(?id) AS ?dco_id) . ";
				$body .= "BIND(str(?desc) AS ?description) . ";
				$body .= "BIND(str(?c_l) AS ?comm_label) . ";
				$body .= "BIND(str(?g_l) AS ?gp_label) . ";
				$body .= "BIND(str(?gt_l) AS ?gt_label) . ";
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
				$body .= "?project vivo:dateTimeInterval [vivo:start [vivo:dateTime ?startDate]] . FILTER (?startDate >= \"" . $constraint_values . "T00:00:00\"^^xsd:dateTime) . ";
			}
			else if ($constraint_type == "endDate") {
				$body .= "?project vivo:dateTimeInterval [vivo:end [vivo:dateTime ?endDate]] . FILTER (?endDate <= \"" . $constraint_values . "T00:00:00\"^^xsd:dateTime) . ";
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
				$body .= "{ ?project dco:associatedDCOCommunity <$constraint_value> }";
				break;
			case "groups":
				$body .= "{ ?project dco:associatedDCOPortalGroup <$constraint_value> }";
				break;
			case "grants":
				$body .= "{ ?project vivo:hasFundingVehicle <$constraint_value> }";
				break;
			case "participants":
				$body .= "{{ ?project obo:BFO_0000055 ?role . } UNION { ?project vivo:contributingRole ?role . } ?role obo:RO_0000052 <$constraint_value> . }";
				break;
            case "reportingYears":
                $body .= "{ ?project dco:hasProjectUpdate ?projectUpdate . ?projectUpdate dco:forReportingYear <$constraint_value> }";
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

		// Output for the request type "projects"				
		if ($type == "projects") {

            // TODO query to pre-populate array for project update info?

			$count = $this->getSearchResultCount($constraints);						
			return $this->getSearchResultsOutput($results, $limit, $offset, $count);
		}
		// Output for the request type "startDate" or "endDate" 
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
