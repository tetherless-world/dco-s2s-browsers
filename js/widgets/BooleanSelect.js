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
edu.rpi.tw.sesf.s2s.widgets.BooleanSelect = function(panel) 
{
    this.panel = panel;
    var input = panel.getInput();
    var select = jQuery("<input id=\"member_check\" type=\"checkbox\" value=\"members\" checked=\"checked\">List DCO Members Only</input>");
    this.div = jQuery("<div class=\"facet-content\" style=\"width:100%\"></div>");
    panel.setInputData(input.getId(), function() {
	var arr = [];
	if(jQuery(select).is(':checked'))
	{
	    arr.push("members");
	} else {
	    arr.push("everyone");
	}
	return arr;
    });
    var self = this;
    jQuery(select).change(function() {
	self.updateState();
        if (jQuery(select).val() != "blank");
        {
            panel.notify();
        }
    });
    jQuery(this.div).append("<br/>");
    jQuery(this.div).append(select);
}


edu.rpi.tw.sesf.s2s.widgets.BooleanSelect.prototype.updateState = function(clicked)
{
    var val = jQuery(clicked).val();
    if (this.state == null) this.state = {};
    if (this.state[val] == null) this.state[val] = 1;
    else delete this.state[val];
}

edu.rpi.tw.sesf.s2s.widgets.BooleanSelect.prototype.getState = function()
{
    return this.state;
}

edu.rpi.tw.sesf.s2s.widgets.BooleanSelect.prototype.setState = function(state)
{
    var self = this;
    jQuery(Object.keys(state)).each(function() {
	    jQuery(self.div).find("input[value=\"" + this + "\"]").attr("checked","checked");
    });
}

edu.rpi.tw.sesf.s2s.widgets.BooleanSelect.prototype.get = function()
{
    return this.div;
}

edu.rpi.tw.sesf.s2s.widgets.BooleanSelect.prototype.reset = function()
{
    var select = jQuery(this.div).find("select.data-selector");
    jQuery(select).children().remove();
}

/*
edu.rpi.tw.sesf.s2s.widgets.BooleanSelect.prototype.update = function(data)
{
    data = JSON.parse(data);
    var input = jQuery(this.div).find("input");
    jQuery(input).children().remove();
    jQuery(data).each(function() 
    {
        var label = this.label && this.label != null ? this.label : this.id;
        if (this.id != null && this.count != null) {
            select.
                append(jQuery("<option></option>").
                    attr("value",this.id).
                    attr("title",'(' + this.count + ') ' + label).
                    text('(' + this.count + ') ' + label));
        }
    });
}
*/

