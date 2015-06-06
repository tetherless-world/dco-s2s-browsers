<?php

include_once("../../../s2s/opensearch/utils.php");

// parent class S2SConfig
include_once("../../../s2s/opensearch/config.php");

class DCO_People_S2SConfig extends S2SConfig {
	
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
		'vcard'	=> "http://www.w3.org/2006/vcard/ns#",
		'vitro-public' => "http://vitro.mannlib.cornell.edu/ns/vitro/public#"
	);

	/**
	* Return SPARQL endpoint URL
	* @return string SPARQL endpoint URL
	*/
	public function getEndpoint() {
		return "http://deepcarbon.tw.rpi.edu:3030/VIVO/query";
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
        * Get affiliations for a given person
        * @param string $person person uri
        * @return array an array of associative arrays containing the affiliation bindings
        */
        private function getAffiliationsByPerson($person) {
                $query = $this->getPrefixes();
                $query .= "SELECT DISTINCT ?position_label ?org ?org_label WHERE { ";
                $query .= "<$person> vivo:relatedBy ?position . ";
		$query .= "?position a vivo:Position . ";
		$query .= "?position rdfs:label ?l . ";
		$query .= "?position vivo:relates ?org . ";
		$query .= "?org a foaf:Organization; rdfs:label ?org_l . ";
                $query .= "BIND(str(?l) AS ?position_label) . ";
		$query .= "BIND(str(?org_l) AS ?org_label) . } ";
                return $this->sparqlSelect($query);
        }

	/**
        * Get emails for a given person
        * @param string $person person uri
        * @return array an array of associative arrays containing the email bindings
        */
        private function getEmailsByPerson($person) {
                $query = $this->getPrefixes();
                $query .= "SELECT DISTINCT ?email WHERE { ";
                $query .= "<$person> obo:ARG_2000028 [vcard:hasEmail ?e] . ";
                $query .= "?e a vcard:Email . ";
                $query .= "?e a vcard:Work . ";
		$query .= "?e vcard:email ?email . } ";
                return $this->sparqlSelect($query);
        }

	/**
        * Get research areas for a given person
        * @param string $person person uri
        * @return array an array of associative arrays containing the research area bindings
        */
        private function getResearchAreasByPerson($person) {
                $query = $this->getPrefixes();
                $query .= "SELECT DISTINCT ?area ?area_label WHERE { ";
                $query .= "<$person> vivo:hasResearchArea ?area . ";
                $query .= "?area rdfs:label ?l . ";
                $query .= "BIND(str(?l) AS ?area_label) . } ";
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
		$count = $result['count'] ;
		return $count ;
	}

	/**
	* Create HTML of search result
	* @param array $result query result to be processed into HTML
	* @return string HTML div of search result entry
	*/
	public function getSearchResultOutput(array $result) {

        if( !isset( $result['person'] ) )
        {
            return "" ;
        }
		$html = "<div class='result-list-item'>";

        // initially set the link to the person's VIVO page but if
        // there's a dco_id use it instead.
        $link = $result['person'] ;
		if( isset( $result['dco_id'] ) )
        {
            $link = $result['dco_id'] ;
        }

		// thumbnail
		if (isset($result['thumbnail'])) {
			$html .= "<a target=\"_blank\" href=\"$link\"><img class=\"result-list-item-thumbnail\" src=\"" . $result['thumbnail'] . "\"></a>";
		}
		else {
			$html .= "<a target=\"_blank\" href=\"$link\"><img class=\"result-list-item-thumbnail\" src=\"https://data.deepcarbon.net/browsers/images/default-user.png\">";
		}

		$html .= "<br />";
		// label
		$html .= "<span class='title'><a target='_blank' href=\"" . $link . "\">" . $result['label'] . "</a></span>";
		
		$html .= "<br />";

		// DCO-ID
                // removing the DCO-ID from the result set. ticket #53.
                // pcw 20150124
                /*
		if (isset($result['dco_id'])) {
			$dco_id_label = substr(@$result['dco_id'], 25);
			$html .= "<br /><span>DCO ID: <a target='_blank' href=\"" . $result['dco_id'] . "\">" . $dco_id_label . "</a></span>";
		}
                */

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

		// organizations
		if (isset($result['organization'])) {
			$html .= "<br /><span>Organization: ";
			$orgs = explode(",", $result['organization']);
			$org_labels = explode(",", $result['organization_label']);
			$orgs_html = array();
			foreach ($orgs as $i => $org) {
				array_push($orgs_html, "<a target='_blank' href=\"" . trim($org) . "\">" . trim($org_labels[$i]) . "</a>");
			}
			$html .= implode(", ", $orgs_html) . "</span>";
		}

		// email
		$emails = $this->getEmailsByPerson($result['person']);
		if (count($emails) > 0) {
			$html .= "<br />Contact: <a href=\"mailto:" . $emails[0]['email'] . "\">Email</a>";
		} 

		// networkId
		if( isset( $result['uid'] ) )
		{
		    $html .= "<br /><span>Id: " . $result['uid'] ;
		}

		$html .= "<br />";

		// Affiliations
                $affiliations = $this->getAffiliationsByPerson($result['person']);
                if (count($affiliations) > 0) {
                        $html .= "<br /><span>Affiliations and Roles: ";
                        $affiliations_markup = array();
                        foreach ($affiliations as $i => $affiliation) {
                                array_push($affiliations_markup, $affiliation['position_label'] . ", <a target='_blank' href=\"" . $affiliation['org'] . "\">" . $affiliation['org_label'] . "</a>");
                        }
                        $html .= implode('; ', $affiliations_markup);
                        $html .= "</span>";
                }	

		$html .= "<br />";

		// Research Areas 
                $areas = $this->getResearchAreasByPerson($result['person']);
                if (count($areas) > 0) {
                        $html .= "<br /><span>Areas of Expertise: ";
                        $areas_markup = array();
                        foreach ($areas as $i => $area) {
                                array_push($areas_markup, "<a target='_blank' href=\"" . $area['area'] . "\">" . $area['area_label'] . "</a>");
                        }
                        $html .= implode(', ', $areas_markup);
                        $html .= "</span>";
                }	

		$html .= "<br style=\"clear:both\" /></div>";
		return $html;
	}
	
	/**
	* Return SPARQL query header component
	* @param string $type search type (e.g. 'datasets', 'authors', 'keywords')
	* @return string query header component (e.g. 'SELECT ?id ?label')
	*/
	public function getQueryHeader( $type )
	{
	    $header = "";
	    switch($type)
	    {
		case "people":
		    $header .= "?person ?dco_id ?label ?thumbnail ";
		    $header .= '(GROUP_CONCAT(DISTINCT ?comm ; SEPARATOR=",") AS ?community) ';
		    $header .= '(GROUP_CONCAT(DISTINCT ?comm_label ; SEPARATOR=",") AS ?community_label) ';
		    $header .= '(GROUP_CONCAT(DISTINCT ?gp ; SEPARATOR=",") AS ?group) ';
		    $header .= '(GROUP_CONCAT(DISTINCT ?gp_label ; SEPARATOR=",") AS ?group_label) ';
		    $header .= '(GROUP_CONCAT(DISTINCT ?org ; SEPARATOR = ",") AS ?organization) ';
		    $header .= '(GROUP_CONCAT(DISTINCT ?org_label ; SEPARATOR = ",") AS ?organization_label) ';
		    $header .= '(GROUP_CONCAT(DISTINCT ?networkId ; SEPARATOR = ",") AS ?uid) ';
		    break;
		case "count":
		    $header .= "(count(DISTINCT ?person) AS ?count)";
		    break;
		default:
		    $header .= "?id ?label (COUNT(DISTINCT ?person) AS ?count)";
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
			case "people":
				$footer .= " GROUP BY ?person ?dco_id ?label ?thumbnail";
				$footer .= " ORDER BY ?label";
				if ($limit)	$footer .= " LIMIT $limit OFFSET $offset";
				break;
			case "count":
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
				$body .= "?person a foaf:Person . ";
				$body .= "?person rdfs:label ?p_l . ";
				$body .= "?person dco:associatedDCOCommunity ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;
				
			case "groups":
				$body .= "?person a foaf:Person . ";
				$body .= "?person rdfs:label ?p_l . ";
				$body .= "?person dco:associatedDCOPortalGroup ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;

			case "organizations":
				$body .= "?person a foaf:Person . ";
				$body .= "?person rdfs:label ?p_l . ";
				$body .= "?person dco:inOrganization ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;
				
			case "count":
				$body .= "?person a foaf:Person . ";
				$body .= "?person rdfs:label ?p_l . ";
				break;
				
			case "people":
				$body .= "?person a foaf:Person ; rdfs:label ?p_l . ";
				$body .= "OPTIONAL { ?person dco:hasDcoId ?id . } ";
				$body .= "OPTIONAL { ?person dco:inOrganization ?org . ?org rdfs:label ?ol . } ";
				$body .= "OPTIONAL { ?person dco:associatedDCOCommunity ?comm . ?comm rdfs:label ?c_l . } ";
				$body .= "OPTIONAL { ?person dco:associatedDCOPortalGroup ?gp . ?gp rdfs:label ?g_l . } ";
				$body .= "OPTIONAL { ?person vitro-public:mainImage [vitro-public:thumbnailImage [vitro-public:downloadLocation ?thumbnail]] . } ";
				$body .= "BIND(str(?p_l) AS ?label) . ";
				$body .= "BIND(str(?id) AS ?dco_id) . ";
				$body .= "BIND(str(?ol) AS ?org_label) . ";
				$body .= "BIND(str(?c_l) AS ?comm_label) . ";
				$body .= "BIND(str(?g_l) AS ?gp_label) . ";
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
			if ($constraint_type == "names") {
				$names = explode(" ", strtolower($constraint_values[0]));
				foreach($names as $i => $name) {
					$body .= "FILTER(regex(str(?p_l), \"" . $name . "\", \"i\"))";
				}
				//$body .= "FILTER(regex(str(?p_l), \"" . strtolower($constraint_values[0]) . "\",\"i\"))";	
			}
			if ($constraint_type == "coordinates") {
				$body .= "?person dco:inOrganization [dco:hasLatitude ?lat; dco:hasLongitude ?long] . ";
				$body .= "FILTER(xsd:float(?lat) >= $constraint_values[1] && xsd:float(?lat) <= $constraint_values[3] && xsd:float(?long) >= $constraint_values[0] && xsd:float(?long) <= $constraint_values[2]) . ";
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
				$body .= "{ ?person dco:associatedDCOCommunity <$constraint_value> }";
				break;
			case "groups":
				$body .= "{ ?person dco:associatedDCOPortalGroup <$constraint_value> }";
				break;
			case "organizations":
				$body .= "{ ?person dco:inOrganization <$constraint_value> }";
				break;
			case "members":
				if( $constraint_value == "members" )
				{
				    $body .= "?person <http://vivo.mydomain.edu/ns#networkId> ?networkId .";
				}
				else
				{
				    $body .= "OPTIONAL {?person <http://vivo.mydomain.edu/ns#networkId> ?networkId . } ";
				}
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
	private function addContextLinks(&$results, $type)
	{
	    if ($type == "communities" || $type == "groups" || $type == "organizations")
	    {
		foreach ( $results as $i => $result )
		{
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
	public function getOutput(array $results, $type, array $constraints, $limit=0, $offset=0)
	{
	    // Output for the request type "people"				
	    if($type == "people")
	    {
		$count = $this->getSearchResultCount($constraints);						
		return $this->getSearchResultsOutput($results, $limit, $offset, $count);
	    }
	    // Output for other types of requests (i.e. search facets)
	    else
	    {		
		$this->addContextLinks($results, $type);
		return $this->getFacetOutput($results);
	    }
	}
}
