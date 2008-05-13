function BlorgMarkupView(id, values, titles)
{
	this.id = id;
	this.values = values;
	this.titles = titles;
	this.textarea = document.getElementById(this.id + '_textarea');

	YAHOO.util.Event.on(this.textarea, 'focus', this.textarea.select);
	YAHOO.util.Event.on(this.textarea, 'click', this.textarea.select);

	this.selected_tab = null;
	this.tabs = [];

	if (this.values.length > 1) {
		this.initTabs();
	}
}

BlorgMarkupView.prototype.initTabs = function()
{
	var span = document.getElementById(this.id).firstChild.firstChild;
	for (var i = 0; i < this.values.length; i++) {
		var anchor = document.createElement('a');
		anchor.href = '#';
		anchor.appendChild(document.createTextNode(this.titles[i]));

		YAHOO.util.Event.on(anchor, 'click',
			function(e, args)
			{
				YAHOO.util.Event.preventDefault(e);
				args[0].selectTabByIndex(args[1]);
			}, [this, i]);

		if (i == 0) {
			YAHOO.util.Dom.addClass(anchor, 'blorg-markup-view-selected');
			span.parentNode.appendChild(document.createTextNode(' '));
		} else {
			span.parentNode.appendChild(document.createTextNode(', '));
		}

		this.tabs.push(anchor);

		span.parentNode.appendChild(anchor);
	}
}

BlorgMarkupView.prototype.selectTabByIndex = function(index)
{
	if (this.values.length > index && this.titles.length > index) {
		if (this.selected_tab) {
			YAHOO.util.Dom.removeClass(this.selected_tab,
				'blorg-markup-view-selected')
		}

		YAHOO.util.Dom.addClass(this.tabs[index], 'blorg-markup-view-selected');
		this.selected_tab = this.tabs[index];

		this.textarea.value = this.values[index];
	}
}
