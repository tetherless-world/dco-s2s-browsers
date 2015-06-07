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
edu.rpi.tw.sesf.s2s.widgets.GenericWidget = function(panel) {
	this.panel = panel;
	var i = panel.getInput();
	var input = jQuery("<input type=\"text\"></input>");
	this.div =  jQuery("<div class=\"facet-content\"></div>");
	panel.setInputData(i.getId(), function() {
		return jQuery(input).val();
	});
	var self = this;
    jQuery(input).change(function() {
		self.updateState();
		panel.notify();
    });
	jQuery(this.div).append(input);
}

edu.rpi.tw.sesf.s2s.widgets.GenericWidget.prototype.updateState = function(clicked)
{
	this.state = this.div.find("input").val();
}

edu.rpi.tw.sesf.s2s.widgets.GenericWidget.prototype.getState = function()
{
	return this.state;
}

edu.rpi.tw.sesf.s2s.widgets.GenericWidget.prototype.setState = function(state)
{
 	this.div.find("input").val(state);
}

edu.rpi.tw.sesf.s2s.widgets.GenericWidget.prototype.reset = function()
{
    this.div.find("input").val("");
}

edu.rpi.tw.sesf.s2s.widgets.GenericWidget.prototype.get = function()
{
	return this.div;
}
