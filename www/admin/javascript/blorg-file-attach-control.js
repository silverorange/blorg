function BlorgFileAttachControl(id, file_id, show)
{
	this.id = id;
	this.file_id = file_id;
	this.show = show;
	this.xml_rpc_client = new XML_RPC_Client('Post/FileAjaxServer');

	this.img = document.getElementById(this.id);
	this.anchor = document.createElement('a');
	this.anchor.href = '#';

	this.anchor.title = (this.show) ?
		BlorgFileAttachControl.detach_text : BlorgFileAttachControl.attach_text;

	this.img.parentNode.replaceChild(this.anchor, this.img);
	this.anchor.appendChild(this.img);

	YAHOO.util.Event.on(this.anchor, 'click', this.handleClick, this, true);
}

BlorgFileAttachControl.attach_on_image = new Image();
BlorgFileAttachControl.attach_on_image.src =
	'packages/blorg/admin/images/file-attach-on.png';

BlorgFileAttachControl.attach_off_image = new Image();
BlorgFileAttachControl.attach_off_image.src =
	'packages/blorg/admin/images/file-attach-off.png';

BlorgFileAttachControl.attach_text = 'attach to this post';
BlorgFileAttachControl.detach_text = 'detach from this post';

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
		that.anchor.title = BlorgFileAttachControl.detach_text;
		that.img.src = BlorgFileAttachControl.attach_on_image.src;
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
		that.anchor.title = BlorgFileAttachControl.attach_text;
		that.img.src = BlorgFileAttachControl.attach_off_image.src;
		that.show = false;
		YAHOO.util.Event.removeListener(that.anchor, 'click',
			BlorgFileAttachControl.voidClickHandler);

		YAHOO.util.Event.on(that.anchor, 'click', that.handleClick, that, true);
	}

	this.xml_rpc_client.callProcedure('detach', callBack,
		[this.file_id], ['int']);

}
