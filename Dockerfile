FROM php:5.6-apache
MAINTAINER Stephan Zednik "zednis2@rpi.edu"
ENV REFRESHED_AT 2015-05-21

ENV WWW_DATA /var/www/html
ENV SERVICE_HOME ${WWW_DATA}/browsers

ADD datasets.html ${SERVICE_HOME}/
ADD opensearch/descriptions/datasets.xml ${SERVICE_HOME}/opensearch/descriptions/datasets.xml
ADD opensearch/services/datasets ${SERVICE_HOME}/opensearch/services/datasets

ADD field_studies.html ${SERVICE_HOME}/
ADD opensearch/descriptions/field_studies.xml ${SERVICE_HOME}/opensearch/descriptions/field_studies.xml
ADD opensearch/services/field_studies ${SERVICE_HOME}/opensearch/services/field_studies

ADD objects.html ${SERVICE_HOME}/
ADD opensearch/descriptions/objects.xml ${SERVICE_HOME}/opensearch/descriptions/objects.xml
ADD opensearch/services/objects ${SERVICE_HOME}/opensearch/services/objects

ADD people.html ${SERVICE_HOME}/
ADD opensearch/descriptions/people.xml ${SERVICE_HOME}/opensearch/descriptions/people.xml
ADD opensearch/services/people ${SERVICE_HOME}/opensearch/services/people

ADD projects.html ${SERVICE_HOME}/
ADD opensearch/descriptions/projects.xml ${SERVICE_HOME}/opensearch/descriptions/projects.xml
ADD opensearch/services/projects ${SERVICE_HOME}/opensearch/services/projects

ADD publications.html ${SERVICE_HOME}/
ADD opensearch/descriptions/publications.xml ${SERVICE_HOME}/opensearch/descriptions/publications.xml
ADD opensearch/services/publications ${SERVICE_HOME}/opensearch/services/publications

COPY ontologies/ ${SERVICE_HOME}/ontologies/
COPY js/ ${SERVICE_HOME}/js/
COPY css/ ${SERVICE_HOME}/css/

COPY s2s/opensearch/ ${SERVICE_HOME}/
COPY s2s/client/ ${SERVICE_HOME}/s2s/client/

#TODO update RDF URIs with sed

VOLUME /var/www/html