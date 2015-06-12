#!/usr/bin/env bash

AVAILABLE_DEPLOYMENTS=" deepcarbon.net
 deepcarbon.net-staging
 udco.tw.rpi.edu
 dcotest.tw.rpi.edu"

replace() {
`find ../*.html -type f -print0 | xargs -0 sed -i "s%$1%$2%g"`
`find ../ontologies -type f -print0 | xargs -0 sed -i "s%$1%$2%g"`
`find ../opensearch -type f -print0 | xargs -0 sed -i "s%$1%$2%g"`
`find ../js -type f -print0 | xargs -0 sed -i "s%$1%$2%g"`
}

case $1 in
    --d=*|--deployment=*)
    DEPLOYMENT="${1#*=}"

    case ${DEPLOYMENT} in

        "deepcarbon.net")
        # TODO add URL replacement assertions (if needed)
        echo "configured for deployment to ${DEPLOYMENT}"
        ;;

        "deepcarbon.net-staging")
        replace "https://data.deepcarbon.net/browsers/" "https://data.deepcarbon.net/staging/browsers/"
        replace "https://data.deepcarbon.net/s2s/" "https://data.deepcarbon.net:8444/s2s/"
        replace "http://fuseki:3030/vivo/query" "http://deepcarbon.tw.rpi.edu:3030/VIVO/query"
        echo "onfigured for deployment to ${DEPLOYMENT}"
        ;;

        "dcotest.tw.rpi.edu")
        replace "https://data.deepcarbon.net/browsers/" "https://dcotest.tw.rpi.edu/browsers/"
        replace "https://data.deepcarbon.net/s2s/" "https://dcotest.tw.rpi.edu/s2s/"
        echo "configured for deployment to ${DEPLOYMENT}"
        ;;

        "udco.tw.rpi.edu")
        replace "https://data.deepcarbon.net/browsers/" "http://udco.tw.rpi.edu/browsers/"
        replace "https://data.deepcarbon.net/s2s/" "http://udco.tw.rpi.edu/s2s/"
        replace "http://fuseki:3030/vivo/query" "http://localhost:3030/vivo/query"
        echo "configured for deployment to ${DEPLOYMENT}"
        ;;

        *)
        echo "unknown deployment: ${DEPLOYMENT}"
        ;;

    esac
    ;;

    *)
    echo "usage: ./config.sh --deployment=DEPLOYMENT"
    echo "available deployments:"
    echo "${AVAILABLE_DEPLOYMENTS}"
    ;;
esac
