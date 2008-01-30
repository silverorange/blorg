function BlorgReplyStatusSlider(id, options)
{
	this.id = id;
	this.options = options;
	this.width = 200;
	this.increment = Math.floor(this.width / (this.options.length - 1));
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
}

BlorgReplyStatusSlider.prototype.createLabels = function()
{
	var body = document.getElementById(this.id);
	var pos = YAHOO.util.Dom.getXY(body);

	for (var i = 0; i < this.options.length; i++) {
		var span = document.createElement('span');
		span.appendChild(document.createTextNode(this.options[i][1]));
		span.style.position = 'absolute';
		span.style.width = this.label_width + 'px';
		span.style.textAlign = 'center';
		span.style.overflow = 'hidden';
		YAHOO.util.Dom.addClass(span, 'blorg-reply-status-slider-legend');
		body.appendChild(span);
		YAHOO.util.Dom.setXY(span,
			[pos[0] + (this.increment * i) - 20, pos[1] + 30]);
	}
}

BlorgReplyStatusSlider.prototype.handleChange = function()
{
	var index = Math.floor(this.slider.getValue() / this.increment);
	this.input.value = this.options[index][0];
}
