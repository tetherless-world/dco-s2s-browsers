/**
 * Create Namespace
 */
if (window.edu == undefined || typeof(edu) != "object") edu = {};
if (edu.rpi == undefined || typeof(edu.rpi) != "object") edu.rpi = {};
if (edu.rpi.tw == undefined || typeof(edu.rpi.tw) != "object") edu.rpi.tw = {};
if (edu.rpi.tw.sesf == undefined || typeof(edu.rpi.tw.sesf) != "object") edu.rpi.tw.sesf = {};
if (edu.rpi.tw.sesf.s2s == undefined || typeof(edu.rpi.tw.sesf.s2s) != "object") edu.rpi.tw.sesf.s2s = {};
if (edu.rpi.tw.sesf.s2s.widgets == undefined || typeof(edu.rpi.tw.sesf.s2s.widgets) != "object") edu.rpi.tw.sesf.s2s.widgets = {};

edu.rpi.tw.sesf.s2s.widgets.DescendingContextSelect = function(panel) {
	this.panel = panel;
	var freetext = jQuery("<input type=\"text\"></input>");
    jQuery(freetext).autocomplete({source: edu.rpi.tw.sesf.s2s.widgets.DescendingContextSelect.autocompleteSource , select: edu.rpi.tw.sesf.s2s.widgets.DescendingContextSelect.autocompleteSelect });
	this.selectbox = jQuery("<div style=\"height:12em;border: 1px solid gray;overflow: auto;\"></div>");
	this.div = jQuery("<div></div>");
	this.div.append(freetext).append(this.selectbox);
}

edu.rpi.tw.sesf.s2s.widgets.DescendingContextSelect.prototype.updateState = function(input)
{
	if (this.state == null) this.state = {};
	if (this.state[input] == null) this.state[input] = 1;
	else delete this.state[input];
}

edu.rpi.tw.sesf.s2s.widgets.DescendingContextSelect.prototype.getState = function()
{
	return this.state;
}

edu.rpi.tw.sesf.s2s.widgets.DescendingContextSelect.prototype.setState = function(state)
{
	var self = this;
	jQuery(Object.keys(state)).each(function() {
		self.updateData(jQuery(self.div).find("input[value=\"" + this + "\"]")[0],true);
	});
}

edu.rpi.tw.sesf.s2s.widgets.DescendingContextSelect.prototype.get = function() {
	return this.div;
}

edu.rpi.tw.sesf.s2s.widgets.DescendingContextSelect.prototype.reset = function() {
	this.selectbox.children().remove();
	this.selectbox.append("<span>Loading...</span>");
}

edu.rpi.tw.sesf.s2s.widgets.DescendingContextSelect.prototype.update = function(data) {
	this.selectbox.children().remove();
	var data = JSON.parse(data);
	
	data.sort(function(o1, o2) {
	    return (o1.label <= o2.label) ? 1 : -1;
	});
	
	for (var i = 0; i < data.length; ++i) {
	    var item = data[i];
		var infoButtonImg = "http://aquarius.tw.rpi.edu/s2s/2.0/ui/images/icon_info_gray.gif";
	    var label = (item['count'] != null) ? item['label'] + " (" + item['count'] + ") " : item['label'];
	    var input;
	    if (item['context'] != null) {
	    	var input = jQuery("<span width=\"100%\" class=\"x-option\"><input type=\"hidden\" value=\"" + item['id'] + "\" /><span title=\""+label+"\"class=\"x-option-label\" style=\"overflow:hidden\">" + label + "</span><a target=\"_blank_\" href=\"" + item['context'] + "\"><img style=\"height:2ex;vertical-align:text-top\" src=\"" + infoButtonImg + "\"></a></span>");
	    }
	    else {
	    	var input = jQuery("<span width=\"100%\" class=\"x-option\"><input type=\"hidden\" value=\"" + item['id'] + "\" /><span title=\""+label+"\"class=\"x-option-label\" style=\"overflow:hidden\">" + label + "</span>");
	    }
	    var self = this;
		jQuery(input).find(".x-option-label").click(function() {
		    self.updateData(this);
		});
	    this.selectbox.append(jQuery("<div style=\"padding-right:1em;padding-left:1em\"></div>").append(input));
	}
	var input = this.panel.getInput().getId();
	var self = this;
	this.panel.setInputData(input, function() {
		var arr = [];
		jQuery(self.div).find(".x-selected").each(function() {
		   	arr.push(jQuery(this).find("input").val());
		});
		return arr;
	});
}

edu.rpi.tw.sesf.s2s.widgets.DescendingContextSelect.prototype.updateData = function(clicked,noprop) {
	if (jQuery(clicked).parent().hasClass("x-selected")) {
		jQuery(clicked).parent().removeClass("x-selected");
		jQuery(clicked).parent().parent().css('background','#FFFFFF');
		jQuery(clicked).parent().parent().css('color','#000000');
		jQuery(clicked).parent().parent().css('border','');
	} else {
		jQuery(clicked).parent().addClass("x-selected");
		jQuery(clicked).parent().parent().css('background','#C7DFFC');
		//jQuery(clicked).parent().parent().css('color','#FFFFFF');
		jQuery(clicked).parent().parent().css('border','1px solid white');
	}
	if (!noprop) { this.updateState("" + jQuery(clicked).parent().find("input").val()); this.panel.notify(); }
}

edu.rpi.tw.sesf.s2s.widgets.DescendingContextSelect.autocompleteSource = function(term, callback)
{
    var t = term;
    var input = this.element;
    var options = jQuery(input).parent().find(".x-option");
    var data = [];
    for (var i = 0; i < options.length; ++i)
	{
	    var o = jQuery(options[i]);
	    if (o.children(".x-option-label").html().toLowerCase().indexOf(term.term.toLowerCase()) > -1)
		{
		   data.push({"label":o.children(".x-option-label").html(),"value":"","option":o.children(".x-option-label")});
		}
	}
    callback(data);
}

edu.rpi.tw.sesf.s2s.widgets.DescendingContextSelect.autocompleteSelect = function(event, ui)
{
    var item = ui.item;
    item.option.click();
    event.target.value = '';
    event.stopPropagation();
}
