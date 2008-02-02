function BlorgReplyStatusSlider(id, options)
{
	this.id = id;
	this.options = options;
	this.width = 200;
	if (this.options.length < 2) {
		// prevent script execution errors when there are no or one option
		this.increment = 1;
	} else {
		this.increment = Math.floor(this.width / (this.options.length - 1));
	}
	this.label_width = this.increment;

	YAHOO.util.Event.onDOMReady(this.init, this, true);
}

BlorgReplyStatusSlider.prototype.init = function()
{
	this.input = document.getElementById(this.id + '_value');

	// create slider object
	this.slider = YAHOO.widget.Slider.getHorizSlider(this.id,
		this.id + '_thumb', 0, this.width, this.increment);

	this.slider.subscribe('change', this.handleChange, this, true);

	var value = parseInt(this.input.value) * this.increment;
	this.slider.setValue(value, true, false, true);

	this.createLabels();
	this.createContextNote();
}

BlorgReplyStatusSlider.prototype.createLabels = function()
{
	var container = document.getElementById(this.id);
	var pos = YAHOO.util.Dom.getXY(container);

	// 8 is pixel position of first tick mark in graphic + css offset
	var x_offset = -Math.floor(this.increment / 2) + 8;
	var y_offset = 30;

	for (var i = 0; i < this.options.length; i++) {
		var span = document.createElement('span');
		span.appendChild(document.createTextNode(this.options[i][1]));
		span.style.position = 'absolute';
		span.style.width = this.label_width + 'px';
		span.style.textAlign = 'center';
		span.style.overflow = 'hidden';
		YAHOO.util.Dom.addClass(span, 'blorg-reply-status-slider-legend');
		container.appendChild(span);
		YAHOO.util.Dom.setXY(span,
			[pos[0] + (this.increment * i) + x_offset, pos[1] + y_offset]);
	}
}

BlorgReplyStatusSlider.prototype.createContextNote = function()
{
	this.context_note = document.createElement('div');
	YAHOO.util.Dom.addClass(this.context_note, 'swat-note');
	YAHOO.util.Dom.addClass(this.context_note,
		'blorg-reply-status-slider-context-note');

	var container = document.getElementById(this.id);
	if (container.nextSibling) {
		container.parentNode.insertBefore(this.context_note,
			container.nextSibling);
	} else {
		container.parentNode.appendChild(this.context_note);
	}

	this.updateContextNote();
}

BlorgReplyStatusSlider.prototype.handleChange = function()
{
	var index = this.getIndex();
	if (this.options.length > 1) {
		this.input.value = this.options[index][0];
	}
	this.updateContextNote();
}

BlorgReplyStatusSlider.prototype.getIndex = function()
{
	return Math.floor(this.slider.getValue() / this.increment);
}

BlorgReplyStatusSlider.prototype.updateContextNote = function()
{
	var index = this.getIndex();
	if (this.context_note.firstChild)
		this.context_note.removeChild(this.context_note.firstChild);

	if (this.options.length > 1) {
		this.context_note.appendChild(
			document.createTextNode(this.options[index][2]));
	}
}
