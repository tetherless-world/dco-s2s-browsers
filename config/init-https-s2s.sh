#!/bin/bash

# The S2S metadata service returns information about the specified search service
# At present the S2S service does not appear to be able to resolve RDF over HTTPS 
# so the S2S config RDF will have to be loaded over HTTP before the metadata service
# can work with search service instances that use HTTPS

# This script should be run after redeploying the S2S servlet to load the S2S config RDF over HTTP

# author: szednik (zednis2@rpi.edu)

AVAILABLE_DEPLOYMENTS=" deepcarbon.net
 deepcarbon.net-staging
 dcotest.tw.rpi.edu"

case $1 in
    --d=*|--deployment=*)
    DEPLOYMENT="${1#*=}"

    case ${DEPLOYMENT} in

        "deepcarbon.net")
        result = `curl -L 'https://data.deepcarbon.net:8444/s2s/metadata?type=services&instance=http%3A//data.deepcarbon.net/browsers/ontologies/dco-s2s.ttl%23PersonSearchService'`
        if [ "$result" -ne '{}' ]; then
          echo "S2S metadata service did not return the expected response"
        fi
        echo "configured HTTPS s2s for deployment to ${DEPLOYMENT}"
        ;;
        
        "deepcarbon.net-staging")
        result = `curl -L 'https://data.deepcarbon.net:8444/s2s/metadata?type=services&instance=http%3A//data.deepcarbon.net/staging/browsers/ontologies/dco-s2s.ttl%23PersonSearchService'`
        if [ "$result" -ne '{}' ]; then
          echo "S2S metadata service did not return the expected response"
        fi
        echo "configured HTTPS s2s for deployment to ${DEPLOYMENT}"
        ;;

        "dcotest.tw.rpi.edu")
        result = `curl -L 'https://dcotest.tw.rpi.edu/s2s/metadata?type=services&instance=http%3A//dcotest.tw.rpi.edu/browsers/ontologies/dco-s2s.ttl%23PersonSearchService'`
        if [ "$result" -ne '{}' ]; then
          echo "S2S metadata service did not return the expected response"
        fi
        echo "configured HTTPS s2s for deployment to ${DEPLOYMENT}"
        ;;

        *)
        echo "unknown deployment: ${DEPLOYMENT}"
        ;;

    esac
    ;;

    *)
    echo "usage: ./init-https-s2s.sh --deployment=DEPLOYMENT"
    echo "available deployments:"
    echo "${AVAILABLE_DEPLOYMENTS}"
    ;;
esac
