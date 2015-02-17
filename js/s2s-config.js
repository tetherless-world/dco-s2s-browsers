(function(s2s,$) {
    /**
     * Globals
     */
    s2s.utils.servletRoot = "http://data.deepcarbon.net:8081/s2s/";
    s2s.utils.metadataService = s2s.utils.servletRoot + "metadata";
    s2s.utils.proxyService = s2s.utils.servletRoot + "proxy";
    s2s.utils.sessionService = "http://data.deepcarbon.net:8081/s2s/session";
    s2s.utils.searchWidgetClass = "http://data.deepcarbon.net/dco-s2s/ontologies/s2s/4/0/InputWidget";
    s2s.utils.resultsWidgetClass = "http://data.deepcarbon.net/dco-s2s/ontologies/s2s/4/0/ResultsWidget";
    s2s.utils.resultsQueryUri = "http://data.deepcarbon.net/dco-s2s/ontologies/s2s/4/0/SearchResultsInterface";
    s2s.utils.hierarchicalSearch = "http://data.deepcarbon.net/dco-s2s/ontologies/s2s-core/4/0/HierarchicalSearch";
    s2s.utils.facetedSearch = "http://data.deepcarbon.net/dco-s2s/ontologies/s2s-core/4/0/FacetedSearch";

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
