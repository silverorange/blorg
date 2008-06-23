function BlorgHeaderImageDisplay(id, file_id)
{
	this.file_id = file_id;
	this.xml_rpc_client = new XML_RPC_Client('Config/HeaderImageAjaxServer');

	this.anchor = document.createElement('a');
	this.anchor.href = '#';
	this.anchor.className = 'blorg-header-image-display';

	if (typeof this.anchor.textContent == 'undefined')
		this.anchor.innerText = BlorgHeaderImageDisplay.delete_text;
	else
		this.anchor.textContent = BlorgHeaderImageDisplay.delete_text;

	YAHOO.util.Event.on(this.anchor, 'click', this.handleClick, this, true);

	var div = document.getElementById(id);
	div.appendChild(this.anchor);

	this.div = div.parentNode.parentNode;
}

BlorgHeaderImageDisplay.confirm_text = 'Remove the Header Image?';
BlorgHeaderImageDisplay.delete_text  = 'Remove Image';

BlorgHeaderImageDisplay.prototype.handleClick = function(e)
{
	YAHOO.util.Event.preventDefault(e);

	if (confirm(BlorgHeaderImageDisplay.confirm_text))
		this.deleteFile();
}

BlorgHeaderImageDisplay.voidClickHandler = function(e)
{
	YAHOO.util.Event.preventDefault(e);
}

BlorgHeaderImageDisplay.prototype.deleteFile = function()
{
	YAHOO.util.Event.removeListener(this.anchor, 'click', this.handleClick);
	YAHOO.util.Event.on(this.anchor, 'click',
		BlorgHeaderImageDisplay.voidClickHandler);

	var that = this;
	function callBack(response)
	{
		that.fadeOut();
	}

	this.xml_rpc_client.callProcedure('delete', callBack,
		[this.file_id], ['int']);
}

BlorgHeaderImageDisplay.prototype.fadeOut = function()
{
	var attributes = { opacity: { to: 0 } };
	var animation = new YAHOO.util.Anim(this.div, attributes, 0.5,
		YAHOO.util.Easing.easeOut);

	animation.onComplete.subscribe(this.shrink, this, true);
	animation.animate();
}

BlorgHeaderImageDisplay.prototype.shrink = function()
{
	this.div.style.overflow = 'hidden';

	var attributes = { height: { to: 0 },
		marginTop: { to: 0 },
		marginBottom: { to: 0 } };

	var animation = new YAHOO.util.Anim(this.div, attributes, 0.25,
		YAHOO.util.Easing.easeOut);

	animation.onComplete.subscribe(
		function() { this.div.parentNode.removeChild(this.div); }, this, true);

	animation.animate();
}
