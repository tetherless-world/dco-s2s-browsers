<?php

include_once("people.php");

// type of requested results as defined in the opensearch document. The
// type will be the name of one of the facets, or the name of the result
// set
$type = null;
// number of results to be displayed in the result set
$limit = 9;
// offset of the current result set, used by the next and previous
// buttons
$offset = 0;
// what to use to sort items in the result set, currently not enabled
//$sort = null;

// array for input constraints
$constraints = array(); // array for input constraints

// Get the keyword constraints from the request url
if (@$_GET['names'] && @$_GET['names'] != '') {
    $constraints['names'] = explode(";",$_GET['names']);
}

// Get the bounding box constraints from the request url
if (@$_GET['box'] && @$_GET['box'] != '') {
	$constraints['coordinates'] = explode(",",$_GET['box']);
}

// Get the community constraints from the request url
if (@$_GET['communities'] && @$_GET['communities'] != '') {
 	$constraints['communities'] = explode(";",$_GET['communities']);
}

// Get the group constraints from the request url
if (@$_GET['groups'] && @$_GET['groups'] != '') {
	$constraints['groups'] = explode(";",$_GET['groups']);
}

// Get the organization constraints from the request url
if (@$_GET['organizations'] && @$_GET['organizations'] != '') {
	$constraints["organizations"] = explode(";",$_GET['organizations']);
}

// Get the organization constraints from the request url
if (@$_GET['members'] && @$_GET['members'] != '') {
	$constraints["members"] = explode(";",$_GET['members']);
}
else
{
    $constraints["members"] = array() ;
    $constraints["members"][0] = "members" ;
}

// Get the number of results displayed on each page from the request url
if (@$_GET['limit'] && @$_GET['limit'] != '') {
    $limit = $_GET['limit'];
}

// Get the offset for the current result set from the request url
if (@$_GET['offset'] && @$_GET['offset'] != '') {
    $offset = $_GET['offset'];
}

// Sorting is currently not enabled
/*
if (@$_GET['sort'] && @$_GET['sort'] != '') {
    $sort = $_GET['sort'];
}
*/

// Get the result type from the request url
if (@$_GET['request'] && @$_GET['request'] != '') {
    $type = $_GET['request'];
}

// instantiate the Config class for the person browser (class
// definition in "people.php")
$s2s = new DCO_People_S2SConfig();

// get the response for the request given the type of request, the
// constraints list to constrain the result, the number of results to
// pull back, the offset into the result set, and what to sort the
// results by. For a facet the response will be a json object. For
// the result set the response will be an HTML document
$out = $s2s->getResponse(@$type, @$constraints, @$limit, @$offset, @$sort);

// for sending the response we want to know the number of characters in
// the result.
$size = strlen($out);

// set the size of the response in the response header
header("Content-length: $size");

// echo the response
echo $out;
