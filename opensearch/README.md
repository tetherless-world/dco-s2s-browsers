This folder includes the files for implementing the OpenSearch RESTful services for s2s browsers.

* descriptions: this folder contains the OpenSearch description XML files which specify the query templates for the RESTful service of each s2s browser instance.
* services: this folder contains the php scripts that implement the OpenSearch RESTful services.
** Each subfolder is an s2s instance, which should contain the following files:
*** <s2sInstanceName>.php (e.g. datasets.php): a config class that extends the "S2SConfig" class and implements the methods in it.
*** search.php: parse the input from urls and initiate the config class.
