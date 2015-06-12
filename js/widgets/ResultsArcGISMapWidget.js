/**
 * Create Namespace
 */
if (window.edu == undefined || typeof(edu) != "object") edu = {};
if (edu.rpi == undefined || typeof(edu.rpi) != "object") edu.rpi = {};
if (edu.rpi.tw == undefined || typeof(edu.rpi.tw) != "object") edu.rpi.tw = {};
if (edu.rpi.tw.sesf == undefined || typeof(edu.rpi.tw.sesf) != "object") edu.rpi.tw.sesf = {};
if (edu.rpi.tw.sesf.s2s == undefined || typeof(edu.rpi.tw.sesf.s2s) != "object") edu.rpi.tw.sesf.s2s = {};
if (edu.rpi.tw.sesf.s2s.widgets == undefined || typeof(edu.rpi.tw.sesf.s2s.widgets) != "object") edu.rpi.tw.sesf.s2s.widgets = {};

/**
 * Construct from Widget object
 */
edu.rpi.tw.sesf.s2s.widgets.ResultsMapWidget = function(panel) {
	this.panel = panel;
	this.div = jQuery("<div style=\"display:inline\"><div style=\"margin-top:3px\" class=\"html\"><div id=\"results-map\"></div></div></div>");
	this.map = null;
}

edu.rpi.tw.sesf.s2s.widgets.ResultsMapWidget.prototype.updateState = function()
{
	this.state = {"map": this.map};
}

edu.rpi.tw.sesf.s2s.widgets.ResultsMapWidget.prototype.setState = function(state)
{
	this.panel.notify(true, true, true);
}

edu.rpi.tw.sesf.s2s.widgets.ResultsMapWidget.prototype.getState = function()
{
	return this.state;
}

edu.rpi.tw.sesf.s2s.widgets.ResultsMapWidget.prototype.get = function()
{
	return this.div;
}

edu.rpi.tw.sesf.s2s.widgets.ResultsMapWidget.prototype.reset = function()
{
	jQuery(this.div).find("#results-map").html("");
	this.map = null;
}

edu.rpi.tw.sesf.s2s.widgets.ResultsMapWidget.prototype.update = function(data)
{
	jQuery(this.div).find("#results-map").children().remove();
	var self = this;

	require([
		"esri/map",
		"esri/layers/FeatureLayer",
		"esri/dijit/PopupTemplate",
		"esri/geometry/Point",
		"esri/geometry/Extent",
		"esri/graphic",
		"dojo/on",
		"dojo/_base/array",
		"dojo/domReady!"
      		], function(Map, FeatureLayer, PopupTemplate, Point, Extent, Graphic, on, array) {
			
			var featureLayer;

        		self.map = new Map("results-map", {
          			basemap: "oceans",
				logo: false,
				showAttribution: false,
          			center: [0, 0],
          			zoom: 2 
        		});	
			self.map.on("mouse-drag", function(evt) {
          			if (self.map.infoWindow.isShowing) {
            				var loc = self.map.infoWindow.getSelectedFeature().geometry;
            				if (!self.map.extent.contains(loc)) {
              					self.map.infoWindow.hide();
            				}
          			}
        		});

			var featureCollection = {
				"layerDefinition": null,
		  		"featureSet": {
		    			"features": [],
		    			"geometryType": "esriGeometryPoint"
		  		}
        		};
        		featureCollection.layerDefinition = {
		  		"geometryType": "esriGeometryPoint",
		 		"objectIdField": "ObjectID",
		  		"drawingInfo": {
		    			"renderer": {
		      				"type": "simple",
		      				"symbol": {
							"type": "esriSMS",
							"style": "esriSMSCircle",
							"size": 8,
							"color": [255, 0, 0, 196],
							"outline": {
			    					"color": [128, 128, 128, 128],
			    					"width": 1,
								"type": "esriSLS",
			    					"style": "esriSLSSolid"
  							}

		      				}
		    			}
		  		},
		  		"fields": [{
		    			"name": "ObjectID",
		    			"alias": "ObjectID",
		    			"type": "esriFieldTypeOID"
		  		}, {
		    			"name": "title",
		    			"alias": "Title",
		    			"type": "esriFieldTypeString"
		  		}, {
		    			"name": "description",
		    			"alias": "Description",
		    			"type": "esriFieldTypeString"
		  		}]
        		};

			var popupTemplate = new PopupTemplate({
          			title: "{title}",
				description: "{description}"
        		});

			featureLayer = new FeatureLayer(featureCollection, {
          			id: 'fieldSitesLayer',
          			infoTemplate: popupTemplate
        		});

			featureLayer.on("click", function(evt) {
          			self.map.infoWindow.setFeatures([evt.graphic]);
        		});

			data = jQuery.parseJSON(data);
        		self.map.on("layers-add-result", function(results) {
				var features = [];
				jQuery.each(data, function(i, field_study) {
					if (typeof field_study.field_sites && field_study.field_sites.length > 0) {
						jQuery.each(field_study.field_sites, function(j, field_site) {
							if (field_site.latitude && typeof field_site.latitude !== 'undefined') {
								var attr = {};
								attr.title = field_site.label;
								attr.description = 
									field_study.thumbnail +
                                                			"<div class=\"popup-text\"><div class=\"field-site-info\"" +
                                                        			"<p>Latitude: " + parseFloat(field_site.latitude).toFixed(2) +
                                                        			"  Longitude: " + parseFloat(field_site.longitude).toFixed(2) + "</p>" +
                                                        			field_site.altitude +
                                                        			field_site.depth +
                                                        			field_site.pressure +
                                                        			field_site.temperature +
                                                        		"</div><div class=\"field-study-info\">" +
                                                        		field_study.label +
                                                        		field_study.dco_id +
                                                        		field_study.leaders +
                                                        		field_study.communities +
                                                        		field_study.groups +
                                                        		field_study.open_to_journalists +
                                               				"</div></div>";
								var geometry = new Point(parseFloat(field_site.longitude), parseFloat(field_site.latitude));
								var graphic = new Graphic(geometry);
          							graphic.setAttributes(attr);
          							features.push(graphic);
							}
						});
					}
				});
				featureLayer.applyEdits(features, null, null);
			});
			
			self.map.addLayers([featureLayer]);
	});
}
