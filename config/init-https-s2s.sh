#!/bin/bash

AVAILABLE_DEPLOYMENTS=" deepcarbon.net
 deepcarbon.net-staging
 dcotest.tw.rpi.edu"

case $1 in
    --d=*|--deployment=*)
    DEPLOYMENT="${1#*=}"

    case ${DEPLOYMENT} in

        "deepcarbon.net")
        `curl https://data.deepcarbon.net:8444/s2s/metadata?type=services&instance=http%3A//data.deepcarbon.net/browsers/ontologies/dco-s2s.ttl%23PersonSearchService`
        echo "configured HTTPS s2s for deployment to ${DEPLOYMENT}"
        ;;
        
        "deepcarbon.net-staging")
        `https://data.deepcarbon.net:8444/s2s/metadata?type=services&instance=http%3A//data.deepcarbon.net/staging/browsers/ontologies/dco-s2s.ttl%23PersonSearchService`
        echo "configured HTTPS s2s for deployment to ${DEPLOYMENT}"
        ;;

        "dcotest.tw.rpi.edu")
        `https://dcotest.tw.rpi.edu/s2s/metadata?type=services&instance=http%3A//dcotest.tw.rpi.edu/browsers/ontologies/dco-s2s.ttl%23PersonSearchService`
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
