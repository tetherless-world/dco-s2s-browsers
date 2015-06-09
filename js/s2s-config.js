(function(s2s,$) {
    /**
     * Globals
     */

    s2s.utils.servletRoot = "https://data.deepcarbon.net/s2s/";
    s2s.utils.metadataService = s2s.utils.servletRoot + "metadata";
    s2s.utils.proxyService = s2s.utils.servletRoot + "proxy";
    s2s.utils.sessionService = s2s.utils.servletRoot + "session";


    s2s.utils.s2sURI = "http://escience.rpi.edu/ontology/sesf/s2s/4/0/";
    s2s.utils.searchWidgetClass = s2s.utils.s2sURI + "InputWidget";
    s2s.utils.resultsWidgetClass = s2s.utils.s2sURI + "ResultsWidget";
    s2s.utils.resultsQueryUri = s2s.utils.s2sURI + "SearchResultsInterface";
    s2s.utils.hierarchicalSearch = s2s.utils.s2sURI + "HierarchicalSearch";
    s2s.utils.facetedSearch = s2s.utils.s2sURI + "FacetedSearch";

    /**
     * Built-in behavior for list-style results
     */
    s2s.utils.limitInput = "http://a9.com/-/spec/opensearch/1.1/count";
    s2s.utils.offsetInput = "http://a9.com/-/spec/opensearch/1.1/startIndex";
    s2s.utils.defaultLimit = 10;
    s2s.utils.defaultOffset = 0;

    /**
     * Other settings
     */
    $.support.cors = true;
})(edu.rpi.tw.sesf.s2s,jQuery);
