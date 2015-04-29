<?php

include_once("../../../s2s/opensearch/utils.php");

// parent class S2SConfig
include_once("../../../s2s/opensearch/config.php");

class DCO_Datasets_S2SConfig extends S2SConfig {
	
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
	* Get DCO authors for a given dataset
	* @param string $dataset dataset uri
	* @return array an array of associative arrays containing the DCO author bindings
	*/
	private function getDCOAuthorsByDataset($dataset) {
		$query = $this->getPrefixes();
		$query .= "SELECT DISTINCT ?uri ?name WHERE { ";
		$query .= "<$dataset> vivo:relatedBy [vivo:relates ?uri ] . ";
		$query .= "?uri a foaf:Person . ";
		$query .= "?uri rdfs:label ?label . ";
		$query .= "BIND(str(?label) AS ?name) } ";
		return $this->sparqlSelect($query);
	}

	/*
	* Get data types for a given dataset
	* @param string $dataset dataset uri
	* @return array an array of associative arrays containing the data types
	*/
	private function getDataTypesByDataset($dataset) {

		$query = $this->getPrefixes();
		$query .= "SELECT DISTINCT ?uri ?dataType_label WHERE { ";
		$query .= "<$dataset> dco:hasDataType ?uri . ";
		$query .= "?uri a dco:DataType . ";
		$query .= "?uri rdfs:label ?label . ";
		$query .= "BIND(str(?label) AS ?dataType_label) } ";
		return $this->sparqlSelect($query);
	}

	/**
	* Get distributions for a given dataset
	* @param string $dataset dataset uri
	* @return array an array of associative arrays containing the distribution bindings 
	*/
	private function getDistributionsByDataset($dataset) {
		$query = $this->getPrefixes();
		$query .= "SELECT DISTINCT ?uri ?label ?access_url WHERE { ";
		$query .= "<$dataset> dco:hasDistribution ?uri . ";
		$query .= "?uri a dcat:Distribution . ";
		$query .= "?uri rdfs:label ?l . ";
		$query .= "?uri dco:accessURL ?access_url . ";
		$query .= "BIND(str(?l) AS ?label) } ";
		return $this->sparqlSelect($query);
	}

	/**
	* Get files for a given dataset
	* @param string $distribution distribution uri
	* @return array an array of associative arrays containing the file bindings 
	*/
	private function getFilesByDistribution($distribution) {
		$query = $this->getPrefixes();
		$query .= "SELECT DISTINCT ?uri ?label ?download_url WHERE { ";
		$query .= "<$distribution> dco:hasFile ?uri . ";
		$query .= "?uri a dco:File . ";
		$query .= "?uri rdfs:label ?l . ";
		$query .= "?uri dco:downloadURL ?download_url . ";
		$query .= "BIND(str(?l) AS ?label) } ";
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
		$dataset_summary_url = "http://deepcarbon.net/dco_dataset_summary?uri=" . $result['dataset'];
		$html .= "<span class='title'><a target='_blank' href=\"" . $dataset_summary_url . "\">" . $result['label'] . "</a></span>";
			
		// DCO-ID
		if (isset($result['dco_id'])) {
			$dco_id_label = substr(@$result['dco_id'], 25);
			$html .= "<br /><span>DCO ID: <a target='_blank' href=\"" . $result['dco_id'] . "\">" . $dco_id_label . "</a></span>";
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

		// authors
		$dco_authors = $this->getDCOAuthorsByDataset($result['dataset']);
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

		// data types
		$datatypes = $this->getDataTypesByDataset($result['dataset']);
		if(count($datatypes) > 0){
			$html .= "<br /><span>Data Types: ";
			$datatypes_markup = array();
				foreach($datatypes as $key => $dataType){
					array_push($datatypes_markup, "<a target='_blank' href=\"" . $dataType['uri'] . "\">" . $dataType['dataType_label'] . "</a>");
				}
			$html .= implode('; ', $datatypes_markup);
			$html .= "</span>";
		}

		// project
		if (isset($result['project'])) {
        	$html .= "<br /><span>Project: <a target='_blank' href=\"" . $result['project'] . "\">" . $result['project_label'] . "</a></span>";
		}

		// distributions & files
		$distributions = $this->getDistributionsByDataset($result['dataset']);
		if (count($distributions) > 0) {
			$html .= "<br ><span>Distributions:<br >";
			$distributions_markup = array();
			foreach ($distributions as $i => $distribution) {
				$files = $this->getFilesByDistribution($distribution['uri']);
				$files_markup = '';
				if (count($files) > 0) {
					$files_markup = " (Direct access: ";
					$files_markup_arr = array();
					foreach ($files as $j => $file) {
						array_push($files_markup_arr, "<a target='_blank' href=\"" . $file['download_url'] . "\">" . $file['label'] . "</a>");
					}
					$files_markup .= implode(', ', $files_markup_arr) . ")";
				}
				array_push($distributions_markup, "<a class='distribution' target='_blank' href=\"" . $distribution['access_url'] . "\">" . $distribution['label'] . "</a>". $files_markup);
			}
			$html .= implode('<br >', $distributions_markup);
			$html .= "</span>";
		}

		// access
		if (isset($result['access'])) {
			$html .= "<br /><span>Access restriction: " . $result['access'] . "</span>";
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
			case "datasets":
				$header .= "?dataset ?dco_id ?label ?year ?project ?project_label ?access ";
				$header .= '(GROUP_CONCAT(DISTINCT ?comm ; SEPARATOR=",") AS ?community) ';
				$header .= '(GROUP_CONCAT(DISTINCT ?comm_label ; SEPARATOR=",") AS ?community_label) ';
				$header .= '(GROUP_CONCAT(DISTINCT ?gp ; SEPARATOR=",") AS ?group) ';
                $header .= '(GROUP_CONCAT(DISTINCT ?gp_label ; SEPARATOR=",") AS ?group_label) ';
				break;
			case "count":
				$header .= "(count(DISTINCT ?dataset) AS ?count)";
				break;
			default:
				$header .= "?id ?label (COUNT(DISTINCT ?dataset) AS ?count)";
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
			case "datasets":
				$footer .= " GROUP BY ?dataset ?dco_id ?label ?year ?project ?project_label ?access";
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
				$body .= "?dataset a vivo:Dataset . ";
				$body .= "?dataset  dco:associatedDCOCommunity ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;
				
			case "groups":
				$body .= "?dataset a vivo:Dataset . ";
				$body .= "?dataset dco:associatedDCOPortalGroup ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;

			case "authors":
				$body .= "?dataset a vivo:Dataset . ";
				$body .= "?dataset vivo:relatedBy [vivo:relates ?id] . ";
				$body .= "?id a foaf:Person . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;

			case "projects":
				$body .= "?dataset a vivo:Dataset . ";
				$body .= "?project a vivo:Project . ";
				$body .= "?project rdfs:label ?l . ";
				$body .= "?project dco:relatedDataset ?dataset . ";
				$body .= "BIND(str(?project) AS ?id) . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;

			case "years":
				$body .= "?dataset a vivo:Dataset . ";
				$body .= "?dataset dco:yearOfPublication ?id . ";
				$body .= "BIND(str(?id) AS ?label) . ";
				break;

			case "datatypes":
				$body .= "?dataset a vivo:Dataset . ";
				$body .= "?dataset  dco:hasDataType ?id . ";
				$body .= "?id rdfs:label ?l . ";
				$body .= "BIND(str(?l) AS ?label) . ";
				break;

			case "count":
				$body .= "?dataset a vivo:Dataset . ";
				break;
				
			case "datasets":
				$body .= "?dataset a vivo:Dataset . ";
				$body .= "?dataset rdfs:label ?l . ";
				$body .= "OPTIONAL { ?dataset dco:hasDcoId ?id . } ";
				$body .= "OPTIONAL { ?dataset dco:yearOfPublication ?y . } ";
				$body .= "OPTIONAL { ?dataset dco:associatedDCOCommunity ?comm . ?comm rdfs:label ?c_l . } ";
				$body .= "OPTIONAL { ?dataset dco:associatedDCOPortalGroup ?gp . ?gp rdfs:label ?g_l . } ";
				$body .= "OPTIONAL { ?project dco:relatedDataset ?dataset ; rdfs:label ?pl . } ";
				$body .= "OPTIONAL { ?dataset obo:ERO_0000045 ?acc . } ";
				$body .= "BIND(str(?l) AS ?label) . ";
				$body .= "BIND(str(?id) AS ?dco_id) . ";
				$body .= "BIND(str(?y) AS ?year) . ";
				$body .= "BIND(str(?c_l) AS ?comm_label) . ";
				$body .= "BIND(str(?g_l) AS ?gp_label) . ";
				$body .= "BIND(str(?acc) AS ?access) . ";
				$body .= "BIND(str(?pl) AS ?project_label) . ";
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
				$body .= "{ ?dataset dco:associatedDCOCommunity <$constraint_value> }";
				break;
			case "groups":
				$body .= "{ ?dataset dco:associatedDCOPortalGroup <$constraint_value> }";
				break;
			case "authors":
				$body .= "{ ?dataset vivo:relatedBy [vivo:relates <$constraint_value>] }";
				break;
			case "projects":
				$body .= "{ <$constraint_value> dco:relatedDataset ?dataset }";
				break;
			case "years":
				$body .= "{ ?dataset dco:yearOfPublication \"$constraint_value\"^^xsd:gYear }";
				break;
			case "datatypes":
				$body .= "{ ?dataset dco:hasDataType <$constraint_value> }";
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
		
		if ($type == "communities" || $type == "groups" || $type == "authors" || $type == "projects" || $type == "datatypes") {
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
		
		// Output for request type "datasets"	
		if($type == "datasets") {
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
