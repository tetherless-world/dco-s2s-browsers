<?php

include_once("../../../s2s/opensearch/utils.php");

// parent class S2SConfig
include_once("../../../s2s/opensearch/config.php");

class DCO_Publications_S2SConfig extends S2SConfig {
	
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

	private $publication_types = "{ ?publication a bibo:Article . } UNION { ?publication a bibo:Book . } UNION { ?publication a bibo:DocumentPart . } UNION { ?publication a dco:Poster . } UNION { ?publication a bibo:Thesis . } ";

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
        * Get DCO authors for a given publication 
        * @param string $publication publication uri
        * @return array an array of associative arrays containing the DCO author bindings
        */
	private function getDCOAuthorsByPublication($publication) {
		$query = $this->getPrefixes();
		$query .= "SELECT DISTINCT ?uri ?name ?rank WHERE { ";
		$query .= "<$publication> vivo:relatedBy ?authorship . ";
		$query .= "?authorship vivo:relates ?uri . ";
		$query .= "?authorship vivo:rank ?rank . ";
		$query .= "?uri a foaf:Person . ";
		$query .= "?uri rdfs:label ?label . ";
		$query .= "BIND(str(?label) AS ?name) } ";
		$query .= "ORDER BY ?rank ";
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
	* Create HTML of search result
	* @param array $result query result to be processed into HTML
	* @return string HTML div of search result entry
	*/
	public function getSearchResultOutput(array $result) {

		$html = "<div class='result-list-item'>";

		// label
		$html .= "<span class='title'>";
		if (isset($result['doi'])) {
			$doi = "http://dx.doi.org/" . $result['doi'];
			$html .= "<a target='_blank' href=\"" . $doi . "\">" . $result['label'] . "</a>";
		}
		else {
			$html .=  $result['label'];
		}
		$html .="</span>";

		// type
		if (isset($result['type'])) {
			$html .= "<br /><span>" . $result['type'] . "</span>";
		}
			
		// DCO-ID
		if (isset($result['dco_id'])) {
			$dco_id_label = substr(@$result['dco_id'], 25);
			$html .= "<br /><span>DCO ID: <a target='_blank' href=\"" . $result['dco_id'] . "\">" . $dco_id_label . "</a></span>";
		}

		// authors
		$dco_authors = $this->getDCOAuthorsByPublication($result['publication']);
		if (count($dco_authors) > 0) {
			$html .= "<br /><span>Authors: ";
			if (count($dco_authors) > 0) {
				$authors_markup = array();
				foreach ($dco_authors as $i => $author) {	
					array_push($authors_markup, "<a target='_blank' href=\"" . $author['uri'] . "\">" . $author['name'] . "</a>");
				}
				$html .= implode('; ', $authors_markup);
			}
			$html .= "</span>";
		}

		// venue
		if (isset($result['venue'])) {
        	$html .= "<br /><span>Published in: " . $result['venue'];
		}
		if (isset($result['year'])) {
			$html .= " (" . $result['year'] . ")";
		}
		if (isset($result['venue'])) {
			$html .= "</span>";
		}

		// DOI
		if (isset($result['doi'])) {
			$html .= "<br /><span>DOI: <a target='_blank' href=\"http://dx.doi.org/" . $result['doi'] . "\">" . $result['doi'] . "</a></span>";
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
			case "publications":
				$header .= "?publication ?dco_id ?label ?type ?doi ?year ?venue ?abstract";
				break;
			case "count":
				$header .= "(count(DISTINCT ?publication) AS ?count)";
				break;
			default:
				$header .= "?id ?label (COUNT(DISTINCT ?publication) AS ?count)";
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
			case "publications":
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
				$body .= $this->publication_types;
				$body .= "?publication  dco:associatedDCOCommunity ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;
				
			case "groups":
				$body .= $this->publication_types;
				$body .= "?publication dco:associatedDCOPortalGroup ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;

			case "authors":
				$body .= $this->publication_types;
				$body .= "?publication vivo:relatedBy [vivo:relates ?id] . ";
				$body .= "?id a foaf:Person . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;

			case "organizations":
				$body .= $this->publication_types;
				$body .= "?publication vivo:relatedBy [vivo:relates ?author] . ";
				$body .= "?author a foaf:Person . ";
				$body .= "?author dco:inOrganization ?organization . ";
				$body .= "?organization rdfs:label ?l . ";
				$body .=  "BIND(str(?organization) AS ?id) . ";
				$body .=  "BIND(str(?l) AS ?label) . ";
				break;

			case "years":
				$body .= $this->publication_types;
				$body .= "?publication dco:yearOfPublication ?id . ";
				$body .= "BIND(str(?id) AS ?label) . ";
				break;

			case "concepts":
				$body .= $this->publication_types;
				$body .= "?publication vivo:hasSubjectArea ?concept . ";
				$body .= "?concept rdfs:label ?l . ";
				$body .= "BIND(str(?concept) AS ?id) . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				
			case "count":
				$body .= $this->publication_types;
				break;
				
			case "publications":
				$body .= $this->publication_types;
				$body .= "?publication rdfs:label ?l; vitro:mostSpecificType [rdfs:label ?t] . ";
				$body .= "OPTIONAL { ?publication dco:hasDcoId ?id . } ";
				$body .= "OPTIONAL { ?publication bibo:doi ?d . } ";
				$body .= "OPTIONAL { ?publication dco:yearOfPublication ?y . } ";
				$body .= "OPTIONAL { ?publication vivo:hasPublicationVenue [rdfs:label ?v] . } ";
				$body .= "OPTIONAL { ?publication bibo:abstract ?a . } ";
				$body .= "BIND(str(?l) AS ?label) . ";
				$body .= "BIND(str(?id) AS ?dco_id) . ";
				$body .= "BIND(str(?y) AS ?year) . ";
				$body .= "BIND(str(?t) AS ?type) . ";
				$body .= "BIND(str(?d) AS ?doi) . ";
				$body .= "BIND(str(?v) AS ?venue) . ";
				$body .= "BIND(str(?a) AS ?abstract) . ";
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
			if ($constraint_type == "keywords") {
				$title_filters = array();
				$abstract_filters = array();
				$body .= ' {?publication rdfs:label ?pl . BIND(str(?pl) AS ?publ) . FILTER(';
				for ($i = 0; $i < count($constraint_values); $i ++) {
					array_push($title_filters, "contains(?publ, \"$constraint_values[$i]\")");
					array_push($abstract_filters, "contains(?pubab, \"$constraint_values[$i]\")");
				}
				$body .= implode(" || ", $title_filters) . ")} UNION ";
				$body .= "{?publication bibo:abstract ?ab . BIND(str(?ab) AS ?pubab) . FILTER(";
				$body .= implode(" || ", $abstract_filters) . ")}";
			}
			else if ($constraint_type == "organizations") {
				$arr = array();	
				foreach($constraint_values as $i => $constraint_value) {
					array_push($arr, "{ ?author dco:inOrganization <$constraint_value> }");
				}
				$body .= "?publication vivo:relatedBy [vivo:relates ?author] . ?author a foaf:Person . " . implode('UNION', $arr) . ' ';
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
				$body .= "{ ?publication dco:associatedDCOCommunity <$constraint_value> }";
				break;
			case "groups":
				$body .= "{ ?publication dco:associatedDCOPortalGroup <$constraint_value> }";
				break;
			case "authors":
				$body .= "{ ?publication vivo:relatedBy [vivo:relates <$constraint_value>] }";
				break;
			case "concepts":
				$body .= "{ ?publication vivo:hasSubjectArea <$constraint_value> }";
				break;
			case "years":
				$body .= "{ ?publication dco:yearOfPublication \"$constraint_value\"^^xsd:gYear }";
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
		
		if ($type == "communities" || $type == "groups" || $type == "authors" || $type == "organizations" || $type == "concepts") {
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

		// Output for the request type "publications"				
		if($type == "publications") {
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
