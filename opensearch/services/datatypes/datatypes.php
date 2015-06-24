<?php

//include_once("../opensearch/utils.php");
include_once("../../../s2s/opensearch/utils.php");

// parent class S2SConfig
//include_once("../opensearch/config.php");
include_once("../../../s2s/opensearch/config.php");

class DCO_Datatypes_S2SConfig extends S2SConfig {
	
	private $namespaces = array(
		'dco'	=> "http://info.deepcarbon.net/schema#",
		'vivo'	=> "http://vivoweb.org/ontology/core#",
		'bibo'	=> "http://purl.org/ontology/bibo/",
		'rdfs'	=> "http://www.w3.org/2000/01/rdf-schema#",
		'xsd'	=> "http://www.w3.org/2001/XMLSchema#",
		'skos'	=> "http://www.w3.org/2004/02/skos/core#",
		'prov'  => "http://www.w3.org/ns/prov#"
	);

	/**
	* Return SPARQL endpoint URL
	* @return string SPARQL endpoint URL
	*/
	public function getEndpoint() {
		return "http://localhost:3030/VIVO/query";
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
	* Get parameters for a given data type
	* @param string $datatype datatype uri
	* @return array an array of associative arrays containing Parameters of a data type
	*/
	private function getParametersByDatatype($datatype) {
		$query = $this->getPrefixes();
		$query .= "SELECT DISTINCT ?uri ?label ?unit WHERE { ";
		$query .= "<$datatype> dco:hasParameter ?uri . ";
		$query .= "?uri a dco:Parameter . ";
		$query .= "?uri rdfs:label ?l . ";
		$query .= "?uri dco:hasUnit ?u . ";
		$query .= "BIND(str(?l) AS ?label) . ";
		$query .= "BIND(str(?u) AS ?unit) } ";
		return $this->sparqlSelect($query);
	}

	/*
	* Get Subject Area for a given datatype
	* @param string $datatype datatype uri
	* @return array an array of associative arrays containing Subject Area of a data type
	*/
	private function getSubjectAreaByDatatype($datatype) {

		$query = $this->getPrefixes();
		$query .= "SELECT DISTINCT ?uri ?label WHERE { ";
		$query .= "<$datatype> dco:dataTypeSubjectArea ?uri . ";
		$query .= "?uri a skos:Concept . ";
		$query .= "?uri rdfs:label ?l . ";
		$query .= "?BIND(str(?l) AS ?label) } ";
		return $this->sparqlSelect($query);
	}

	/*
	* Get Datasets for a given datatype
	* @param string $datatype datatype uri
	* @return array an array of associative arrays containing datasets assoicated with a data type
	*/
	private function getDatasetsByDatatype($datatype) {

		$query = $this->getPrefixes();
		$query .= "SELECT DISTINCT ?uri ?id ?label WHERE { ";
		$query .= "?uri dco:hasDataType <$datatype> . ";
		$query .= "?uri a vivo:Dataset . ";
		$query .= "?uri dco:hasDcoId ?id . ";
		$query .= "?uri rdfs:label ?l . ";
		$query .= "BIND(str(?l) AS ?label) } ";
		return $this->sparqlSelect($query);
	}

	/*
	* Get details of a data type
	* @param string $datatype datatype uri
	* @return array an array of associative arrays containing details of a data type
	*/
	private function getDetailsOfDatatype($datatype) {

		$query = $this->getPrefixes();
		$query .= "SELECT DISTINCT ?id ?label ?creationTime ?lastModifiedTime ?expectedUses WHERE { ";
		$query .= "<$datatype> dco:hasDcoId ?id . ";
		$query .= "<$datatype> rdfs:label ?l . ";
		$query .= "<$datatype> dco:createdAtTime ?ct . ";
		$query .= "<$datatype> dco:lastModifiedAtTime ?lt . ";
		$query .= "<$datatype> dco:expectedUses ?eu . ";
		$query .= "BIND(str(?l) AS ?label) . ";
		$query .= "BIND(str(?ct) AS ?creationTime) . ";
		$query .= "BIND(str(?lt) AS ?lastModifiedTime) . ";
		$query .= "BIND(str(?eu) AS ?expectedUses) } ";
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
		$datatype_summary_url = "http://deepcarbon.net/dco_datatype_summary?uri=" . $result['datatype'];
		$html .= "<span class='title'><a target='_blank' href=\"" . $datatype_summary_url . "\">" . $result['label'] . "</a></span>";
			
		// DCO-ID
		if( isset( $result['dco_id'] ) )
		{
		    $dco_id_label = substr(@$result['dco_id'], 25);
		    $html .= "<br /><span>DCO ID: <a target='_blank' href=\"" . $result['dco_id'] . "\">" . $dco_id_label . "</a></span>";
		}

		// Author
		if( isset( $result['creator'] ) )
		{
			$html .= "<br /><span>Author: ";
			$auth_arr = explode("|", $result['creator']);
			$auth_label_arr = explode("|", $result['creator_label']);
			$authors_markup = array();
			foreach ($auth_arr as $i => $auth) {
				array_push($authors_markup, "<a target='_blank' href=\"" . $auth . "\">" . $auth_label_arr[$i] . "</a>");
			}
			$html .= implode('; ', $authors_markup);
			$html .= "</span>";
		}

		// Datatype details
		/*
		$datatypeDetails = $this->getDetailsOfDatatype($result['datatype']);
		if( count( $datatypeDetails ) > 0 )
		{
		    $html .= "<br /><span>Details: ";
		    $datatypeDetails_markup = array();
		    foreach( $datatypeDetails as $key => $datatypeDetail )
		    {
			array_push($datatypeDetails_markup, "<a target='_blank' href=\"" . $datatype_summary_url . "\">" . $datatypeDetail['label'] . "</a>", "<br>" . $datatypeDetail['id'], "<br>" . $datatypeDetail['creationTime'], "<br>" . $datatypeDetail['lastModifiedTime'], "<br>" . $datatypeDetail['expectedUses']);
		    }
		    $html .= implode('; ', $datatypeDetails_markup);
		    $html .= "</span>";
		}
		*/

		// Source Standard
		if( isset( $result['standard'] ) )
		{
		    $html .= "<br /><span>Source Standard: ";
		    $standard_arr = explode("|", $result['standard']);
		    $standard_label_arr = explode("|", $result['standard_label']);
		    $standard_markup = array();
		    foreach( $standard_arr as $i => $standard )
		    {
			$slabel = $standard_label_arr[$i] ;
			array_push( $standard_markup, "<a target='_blank' href=\"" . $standard . "\">" . $slabel . "</a>");
		    }
		    $html .= implode('; ', $standard_markup);
		    $html .= "</span>";
		}

		// Parameters
		if( isset( $result['param'] ) )
		{
		    $html .= "<br /><span>Parameters: ";
		    $param_arr = explode("|", $result['param']);
		    $param_label_arr = explode("|", $result['param_label']);
		    $param_markup = array();
		    foreach( $param_arr as $i => $param )
		    {
			$plabel = $param_label_arr[$i] ;
			array_push( $param_markup, "<a target='_blank' href=\"" . $param . "\">" . $plabel . "</a>");
		    }
		    $html .= implode('; ', $param_markup);
		    $html .= "</span>";
		}

		// Subject Area
		/*
		$subjectAreas = $this->getSubjectAreaByDatatype($result['datatype']);
		if(count($subjectAreas) > 0){
			$html .= "<br /><span>Subject Area: ";
			$subjectAreas_markup = array();
				foreach($subjectAreas as $key => $subjectArea){
					array_push($subjectAreas_markup, "<a target='_blank' href=\"" . $subjectArea['uri'] . "\">" . $subjectArea['label'] . "</a>");
				}
			$html .= implode('; ', $subjectAreas_markup);
			$html .= "</span>";
		}

		// Datasets
		$datasets = $this->getDatasetsByDatatype($result['datatype']);
		if(count($datasets) > 0){
			$html .= "<br /><span>Datasets: ";
			$datasets_markup = array();
				foreach($datasets as $key => $dataset){
					array_push($datasets_markup, "<a target='_blank' href=\"" . $dataset['uri'] . "\">" . $dataset['label'] . "</a>", "<br>" . $dataset['id']);
				}
			$html .= implode('; ', $datasets_markup);
			$html .= "</span>";
		}

		// access
		/*if (isset($result['access'])) {
			$html .= "<br /><span>Access restriction: " . $result['access'] . "</span>";
		}
                */

		$html .= "</div>";
		return $html;
	}
	
	/**
	* Return SPARQL query header component
	* @param string $type search type (e.g. 'parameters', 'authors', 'keywords')
	* @return string query header component (e.g. 'SELECT ?id ?label')
	*/
	public function getQueryHeader($type) {
	
		$header = "";
		switch($type) {
			case "datatypes":
			    $header .= "?datatype ?dco_id ?label ?year " ;
			    $header .= "(GROUP_CONCAT(DISTINCT ?p ; SEPARATOR=\"|\") AS ?param) " ;
			    $header .= "(GROUP_CONCAT(DISTINCT ?p_label ; SEPARATOR=\"|\") AS ?param_label) " ;
			    $header .= "(GROUP_CONCAT(DISTINCT ?cr ; SEPARATOR=\"|\") AS ?creator)  " ;
			    $header .= "(GROUP_CONCAT(DISTINCT ?c_label ; SEPARATOR=\"|\") AS ?creator_label) " ;
			    $header .= "(GROUP_CONCAT(DISTINCT ?st ; SEPARATOR=\"|\") AS ?standard)  " ;
			    $header .= "(GROUP_CONCAT(DISTINCT ?s_label ; SEPARATOR=\"|\") AS ?standard_label) " ;
			    break;
			case "count":
                            $header .= "(count(DISTINCT ?datatype) AS ?count)";
                            break;
			default:
			    $header .= "?id ?label (COUNT(DISTINCT ?datatype) AS ?count)";
			    break;
		}
		return $header;
	}
	
	/**
	* Return SPARQL query footer component
	* @param string $type search type (e.g. 'parameters', 'authors', 'keywords')
	* @param int $limit size of result set
	* @param int $offset offset into result set
	* @param string $sort query result sort parameter
	* @return string query footer component (e.g. 'GROUP BY ?label ?id')
	*/
	public function getQueryFooter($type, $limit=null, $offset=0, $sort=null) {
	
		$footer = "";
		switch($type) {
                    case "datatypes":
                        $footer .= "GROUP BY ?datatype ?dco_id ?label ?year " ;
                        $footer .= "ORDER BY ?label " ;
                        if( $limit ) $footer .= " LIMIT $limit OFFSET $offset";
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
			case "creationYear":
				$body .= "?datatype a dco:DataType . ";
				$body .= "?datatype  dco:createdAtTime ?id . ";
				$body .= "BIND(str(?id) AS ?label) . ";
				break;
				
			case "creator":
				$body .= "?datatype a dco:DataType . ";
				$body .= "?datatype prov:wasAttributedTo ?id . ";
				$body .= "?id a prov:Agent . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;

			case "parameter":
				$body .= "?datatype a dco:DataType . ";
				$body .= "?datatype dco:hasParameter ?id . ";
				$body .= "?id a dco:Parameter . " ;
				$body .= "?id rdfs:label ?l .";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;

			case "sourceStandard":
				$body .= "?datatype a dco:DataType . ";
				$body .= "?datatype dco:sourceStandard ?id . ";
				$body .= "?id a bibo:Standard . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;

			case "subjectArea":
				$body .= "?datatype a dco:DataType . ";
				$body .= "?datatype dco:dataTypeSubjectArea ?id . ";
				$body .= "?id a skos:Concept . ";
				$body .= "?id rdfs:label ?l .";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;

			case "count":
				$body .= "?datatype a dco:DataType . ";
				break;
				
			case "datatypes":
                            $body .= "?datatype a dco:DataType . " ;
                            $body .= "?datatype rdfs:label ?l . " ;
                            $body .= "OPTIONAL { ?datatype dco:hasDcoId ?id . } " ;
                            $body .= "OPTIONAL { ?datatype dco:createdAtTime ?ct . } " ;
                            $body .= "OPTIONAL { ?datatype dco:hasParameter ?p . ?p a dco:Parameter . ?p rdfs:label ?pl . } " ;
                            $body .= "OPTIONAL { ?datatype prov:wasAttributedTo ?cr . ?cr a prov:Agent . ?cr rdfs:label ?cl . } " ;
                            $body .= "OPTIONAL { ?datatype dco:sourceStandard ?st . ?st a bibo:Standard . ?st rdfs:label ?sl . } " ;
                            $body .= "BIND(str(?l) AS ?label) . " ;
                            $body .= "BIND(str(?id) AS ?dco_id) . " ;
                            $body .= "BIND(str(?ct) AS ?year) . " ;
                            $body .= "BIND(str(?pl) as ?p_label) . " ;
                            $body .= "BIND(str(?cl) as ?c_label) . " ;
                            $body .= "BIND(str(?sl) as ?s_label) . " ;
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
			case "creationYear":
				$body .= "  { ?datatype dco:createdAtTime ?act . FILTER (xsd:string(?act) = \"$constraint_value\"^^xsd:string ) } ";
				break;
			case "creator":
				$body .= "{ ?datatype prov:wasAttributedTo <$constraint_value> } ";
				break;
			case "parameter":
				$body .= "{ ?datatype dco:hasParameter <$constraint_value> }";
				break;
			case "sourceStandard":
				$body .= "{ ?datatype dco:sourceStandard <$constraint_value> }";
				break;
			case "subjectArea":
				$body .= "{ ?datatype dco:dataTypeSubjectArea <$constraint_value> }";
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
		
		if ($type == "creator" || $type == "parameter" || $type == "sourceStandard" || $type == "subjectArea") {
			foreach ( $results as $i => $result ) {
				$results[$i]['context'] = $result['id']; 
			}
		}
	}
	
	/**
	* Return representation (HTML or JSON) of response to send to client
	* @param array $results array of associative arrays with bindings from query execution
	* @param string $type search type (e.g. 'parameter', 'authors', 'keywords')
	* @param array $constraints array of arrays with search constraints
	* @param int $limit size of result set
	* @param int $offset offset into result set
	* @return string representation of response to client
	*/
	public function getOutput(array $results, $type, array $constraints, $limit=0, $offset=0) {
		
		// Output for request type "datatypes"	
		if($type == "datatypes") {
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

