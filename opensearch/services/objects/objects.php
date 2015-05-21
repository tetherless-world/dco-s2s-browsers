<?php

include_once("../../../s2s/opensearch/utils.php");

// parent class S2SConfig
include_once("../../../s2s/opensearch/config.php");

class DCO_Objects_S2SConfig extends S2SConfig {
	
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
	* Create HTML of search result
	* @param array $result query result to be processed into HTML
	* @return string HTML div of search result entry
	*/
	public function getSearchResultOutput(array $result) {}

	/**
	* Return HTML representation of search results to display
	* @param array $results array of associative arrays with bindings from query execution
	* @param int $limit size of result set
	* @param int $offset offset into result set
	* @param int $count total count of result set
	* @return string HTML encoding of search results to display
	*/
	public function getSearchResultsOutput(array $results, $limit=0, $offset=0, $count=0) {

		header("Access-Control-Allow-Origin: *");
		header("Content-Type: text/html");
				
		if($this->setCacheControlHeader()) {
			header($this->getSearchResultCacheControlHeader());
		}
				
		$html = "";
		if ($count > 0) {
			$html .= "<div>";
			$html .= "<input type='hidden' name='startIndex' value='$offset'/>";
			$html .= "<input type='hidden' name='itemsPerPage' value='$limit'/>";
			$html .= "<input type='hidden' name='totalResults' value='$count'/>";
			$html .= "</div>";
		}
			
		$html .= "<div class='result-list'>";
		$section = $results[0]['type'];	
		$html .= "<div class='result-list-section-header'>$section</div>";
		foreach ($results as $i => $result) {
			if ($result['type'] != $section) {
				$section = $result['type'];
				$html .= "<div class='result-list-section-header'>$section</div>";
			}

			// title
			$html .= "<div class='result-list-item'><span class='title'>";
			$html .=  "<a target='_blank' href=\"" . $result['object'] . "\">" . $result['label'] . "</a>";
			$html .= "</span>";

			// type
			$html .= "<br /><span>" . $result['type'] . "</span>";

			// DCO-ID
			if (isset($result['dco_id'])) {
				$dco_id_label = substr(@$result['dco_id'], 25);
				$html .= "<br /><span>DCO ID: <a target='_blank' href=\"" . $result['dco_id'] . "\">" . $dco_id_label . "</a></span>";
			}
			$html .= "</div>";
		}
		$html .= "</div>";
		return $html;
	}
	
	/**
	* Return SPARQL query header component
	* @param string $type search type (e.g. 'datasets', 'authors', 'keywords')
	* @return string query header component (e.g. 'SELECT ?id ?label')
	*/
	public function getQueryHeader($type) {
	
		$header = "";
		switch($type) {
			case "objects":
				$header .= "?object ?dco_id ?label ?type";
				break;
			case "count":
				$header .= "(count(DISTINCT ?object) AS ?count)";
				break;
			default:
				$header .= "?id ?label (COUNT(DISTINCT ?object) AS ?count)";
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
			case "objects":
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
				$body .= "?object a [rdfs:subClassOf dco:Object] . ";
				$body .= "?object dco:associatedDCOCommunity ?community . ";
				$body .= "?community rdfs:label ?l . ";
				$body .= "BIND(str(?community) AS ?id) . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;
				
			case "types":
				$body .= "?object a ?class . ";
				$body .= "?class rdfs:subClassOf dco:Object ; rdfs:label ?l . ";
				$body .= "BIND(str(?class) AS ?id) . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;
				
			case "count":
				$body .= "?object a [rdfs:subClassOf dco:Object] . ";
				break;
				
			case "objects":
				$body .= "?object a [rdfs:subClassOf dco:Object] ; rdfs:label ?l . ";
				$body .= "?object vitro:mostSpecificType [rdfs:label ?t] . ";
				$body .= "OPTIONAL { ?object dco:hasDcoId ?id . } ";
				$body .= "BIND(str(?l) AS ?label) . ";
				$body .= "BIND(str(?id) AS ?dco_id) . ";
				$body .= "BIND(str(?t) AS ?type) . ";
				break;
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
				$body .= "{ ?object dco:associatedDCOCommunity <$constraint_value> } UNION { ?object dco:projectAssociatedWith <$constraint_value> }";
				break;
			case "types":
				$body .= "{ ?object a <$constraint_value> }";
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
		
		if ($type == "communities") {
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
	
		// Output for the request type "objects"			
		if($type == "objects") {
			// Sort the results by object types
			usort($results, function($a, $b) {
				return strnatcmp($a['type'], $b['type']);
			});
			$count = $this->getSearchResultCount($constraints);						
			return $this->getSearchResultsOutput($results, $limit, $offset, $count);
		}
		// Output for other types of requests (i.e. search facets)
		else {		
			$this->addContextLinks($results, $type);
			return $this->getFacetOutput($results);
		}
	}
}
