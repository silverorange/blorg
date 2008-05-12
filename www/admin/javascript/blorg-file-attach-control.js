function BlorgFileAttachControl(id, file_id, show)
{
	this.id = id;
	this.file_id = file_id;
	this.show = show;
	this.xml_rpc_client = new XML_RPC_Client('Post/FileAjaxServer');

	this.span = document.createElement('span');
	var text = (this.show) ?
		BlorgFileAttachControl.attached_text :
		BlorgFileAttachControl.detached_text;

	if (typeof this.span.textContent == 'undefined')
		this.span.innerText = text;
	else
		this.span.textContent = text;

	this.anchor = document.createElement('a');
	this.anchor.href = '#';

	var text = (this.show) ?
		BlorgFileAttachControl.detach_text :
		BlorgFileAttachControl.attach_text;

	if (typeof this.anchor.textContent == 'undefined')
		this.anchor.innerText = text;
	else
		this.anchor.textContent = text;

	YAHOO.util.Event.on(this.anchor, 'click', this.handleClick, this, true);

	var span = document.getElementById(this.id);
	span.appendChild(this.span);
	span.appendChild(document.createTextNode('\u00a0'));
	span.appendChild(this.anchor);
}

BlorgFileAttachControl.attach_on_image = new Image();
BlorgFileAttachControl.attach_on_image.src =
	'packages/blorg/admin/images/file-attach-on.png';

BlorgFileAttachControl.attach_off_image = new Image();
BlorgFileAttachControl.attach_off_image.src =
	'packages/blorg/admin/images/file-attach-off.png';

BlorgFileAttachControl.attach_text = 'Attach';
BlorgFileAttachControl.detach_text = 'Detach';
BlorgFileAttachControl.attach_text = 'Attach';
BlorgFileAttachControl.detach_text = 'Detach';

BlorgFileAttachControl.prototype.handleClick = function(e)
{
	YAHOO.util.Event.preventDefault(e);
	this.toggle();
}

BlorgFileAttachControl.prototype.toggle = function()
{
	if (this.show) {
		this.detach();
	} else {
		this.attach();
	}
}

BlorgFileAttachControl.voidClickHandler = function(e)
{
	YAHOO.util.Event.preventDefault(e);
}

BlorgFileAttachControl.prototype.attach = function()
{
	YAHOO.util.Event.removeListener(this.anchor, 'click', this.handleClick);
	YAHOO.util.Event.on(this.anchor, 'click',
		BlorgFileAttachControl.voidClickHandler);

	var that = this;
	function callBack(response)
	{
		if (typeof that.anchor.textContent == 'undefined') {
			that.anchor.innerText = BlorgFileAttachControl.detach_text;
			that.span.innerText = BlorgFileAttachControl.attached_text;
		} else {
			that.anchor.textContent = BlorgFileAttachControl.detach_text;
			that.span.innerText = BlorgFileAttachControl.attached_text;
		}

		that.show = true;
		YAHOO.util.Event.removeListener(that.anchor, 'click',
			BlorgFileAttachControl.voidClickHandler);

		YAHOO.util.Event.on(that.anchor, 'click', that.handleClick, that, true);
	}

	this.xml_rpc_client.callProcedure('attach', callBack,
		[this.file_id], ['int']);
}

BlorgFileAttachControl.prototype.detach = function()
{
	YAHOO.util.Event.removeListener(this.anchor, 'click', this.handleClick);
	YAHOO.util.Event.on(this.anchor, 'click',
		BlorgFileAttachControl.voidClickHandler);

	var that = this;
	function callBack(response)
	{
		if (typeof that.anchor.textContent == 'undefined') {
			that.anchor.innerText = BlorgFileAttachControl.attach_text;
			that.span.innerText = BlorgFileAttachControl.detached_text;
		} else {
			that.anchor.textContent = BlorgFileAttachControl.attach_text;
			that.span.textContent = BlorgFileAttachControl.detached_text;
		}

		that.show = false;
		YAHOO.util.Event.removeListener(that.anchor, 'click',
			BlorgFileAttachControl.voidClickHandler);

		YAHOO.util.Event.on(that.anchor, 'click', that.handleClick, that, true);
	}

	this.xml_rpc_client.callProcedure('detach', callBack,
		[this.file_id], ['int']);

}
