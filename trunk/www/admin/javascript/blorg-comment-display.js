function BlorgCommentDisplay(id, comment_status, spam)
{
	this.id = id;
	this.comment_status = comment_status;
	this.comment_spam = spam;

	var id_split = id.split('_', 2);
	this.comment_id = (id_split[1]) ? id_split[1] : id_split[0];

	this.initControls();
	this.initConfirmation();
	this.container = document.getElementById(this.id);
	this.status_container = document.getElementById(this.id + '_status');
}

BlorgCommentDisplay.approve_text   = 'Approve';
BlorgCommentDisplay.deny_text      = 'Deny';
BlorgCommentDisplay.publish_text   = 'Publish';
BlorgCommentDisplay.unpublish_text = 'Unpublish';
BlorgCommentDisplay.spam_text      = 'Spam';
BlorgCommentDisplay.not_spam_text  = 'Not Spam';
BlorgCommentDisplay.delete_text    = 'Delete';
BlorgCommentDisplay.cancel_text    = 'Cancel';

BlorgCommentDisplay.status_spam_text        = 'Spam';
BlorgCommentDisplay.status_pending_text     = 'Pending';
BlorgCommentDisplay.status_unpublished_text = 'Unpublished';

BlorgCommentDisplay.delete_confirmation_text = 'Delete comment?';

BlorgCommentDisplay.STATUS_PENDING     = 0;
BlorgCommentDisplay.STATUS_PUBLISHED   = 1;
BlorgCommentDisplay.STATUS_UNPUBLISHED = 2;

BlorgCommentDisplay.xml_rpc_client = new XML_RPC_Client(
	'Post/CommentAjaxServer');

// {{{ initControls()

BlorgCommentDisplay.prototype.initControls = function()
{
	var controls_div = document.getElementById(this.id + '_controls');

	this.approve_button = document.createElement('input');
	this.approve_button.type = 'button';
	this.approve_button.value = BlorgCommentDisplay.approve_text;
	YAHOO.util.Event.on(this.approve_button, 'click',
		this.publish, this, true);

	this.deny_button = document.createElement('input');
	this.deny_button.type = 'button';
	this.deny_button.value = BlorgCommentDisplay.deny_text;
	YAHOO.util.Event.on(this.deny_button, 'click',
		this.unpublish, this, true);

	this.publish_toggle_button = document.createElement('input');
	this.publish_toggle_button.type = 'button';
	this.publish_toggle_button.value = BlorgCommentDisplay.publish_text;
	YAHOO.util.Event.on(this.publish_toggle_button, 'click',
		this.togglePublished, this, true);

	this.spam_toggle_button = document.createElement('input');
	this.spam_toggle_button.type = 'button';
	this.spam_toggle_button.value = BlorgCommentDisplay.spam_text;
	YAHOO.util.Event.on(this.spam_toggle_button, 'click',
		this.toggleSpam, this, true);

	this.delete_button = document.createElement('input');
	this.delete_button.type = 'button';
	this.delete_button.value = BlorgCommentDisplay.delete_text;
	YAHOO.util.Event.on(this.delete_button, 'click',
		this.confirmDelete, this, true);

	if (this.comment_status == BlorgCommentDisplay.STATUS_PUBLISHED) {
		this.publish_toggle_button.value = BlorgCommentDisplay.unpublish_text;
	}

	if (this.comment_spam) {
		this.spam_toggle_button.value = BlorgCommentDisplay.not_spam_text;
		this.approve_button.style.display = 'none';
		this.deny_button.style.display = 'none';
		this.publish_toggle_button.style.display = 'none';
	} else {
		switch (this.comment_status) {
		case BlorgCommentDisplay.STATUS_PENDING:
			this.publish_toggle_button.style.display = 'none';
			break;

		case BlorgCommentDisplay.STATUS_PUBLISHED:
		case BlorgCommentDisplay.STATUS_UNPUBLISHED:
			this.approve_button.style.display = 'none';
			this.deny_button.style.display = 'none';
			break;
		}
	}

	controls_div.appendChild(this.approve_button);
	controls_div.appendChild(document.createTextNode(' '));
	controls_div.appendChild(this.deny_button);
	controls_div.appendChild(document.createTextNode(' '));
	controls_div.appendChild(this.publish_toggle_button);
	controls_div.appendChild(document.createTextNode(' '));
	controls_div.appendChild(this.spam_toggle_button);
	controls_div.appendChild(document.createTextNode(' '));
	controls_div.appendChild(this.delete_button);
}

// }}}
// {{{ initConfirmation()

BlorgCommentDisplay.prototype.initConfirmation = function()
{
	this.confirmation = document.createElement('div');
	this.confirmation.className = 'blorg-comment-display-confirmation';
	this.confirmation.style.display = 'none';

	var message_div = document.createElement('div');
	BlorgCommentDisplay.setTextContent(message_div,
		BlorgCommentDisplay.delete_confirmation_text);

	this.confirmation.appendChild(message_div);

	this.confirmation_cancel = document.createElement('input');
	this.confirmation_cancel.type ='button';
	this.confirmation_cancel.value = 'Cancel'; //TODO
	this.confirmation.appendChild(this.confirmation_cancel);
	YAHOO.util.Event.on(this.confirmation_cancel, 'click', this.cancelDelete,
		this, true);

	this.confirmation.appendChild(document.createTextNode(' '));

	this.confirmation_ok = document.createElement('input');
	this.confirmation_ok.type ='button';
	this.confirmation_ok.value = BlorgCommentDisplay.delete_text;
	this.confirmation.appendChild(this.confirmation_ok);
	YAHOO.util.Event.on(this.confirmation_ok, 'click', this.deleteComment,
		this, true);

	this.delete_button.parentNode.appendChild(this.confirmation);
}

// }}}
// {{{ publish()

BlorgCommentDisplay.prototype.publish = function()
{
	this.setSensitivity(false);

	var that = this;
	function callBack(response)
	{
		that.comment_status = BlorgCommentDisplay.STATUS_PUBLISHED;

		that.approve_button.style.display = 'none';
		that.deny_button.style.display = 'none';
		that.publish_toggle_button.style.display = 'inline';
		that.publish_toggle_button.value = BlorgCommentDisplay.unpublish_text;

		YAHOO.util.Dom.removeClass(that.container, 'blorg-comment-red');
		YAHOO.util.Dom.removeClass(that.container, 'blorg-comment-yellow');
		YAHOO.util.Dom.addClass(that.container, 'blorg-comment-green');

		that.updateStatus();
		that.setSensitivity(true);
	}

	BlorgCommentDisplay.xml_rpc_client.callProcedure('publish', callBack,
		[this.comment_id], ['int']);
}

// }}}
// {{{ unpublish()

BlorgCommentDisplay.prototype.unpublish = function()
{
	this.setSensitivity(false);

	var that = this;
	function callBack(response)
	{
		that.comment_status = BlorgCommentDisplay.STATUS_UNPUBLISHED;

		that.approve_button.style.display = 'none';
		that.deny_button.style.display = 'none';
		that.publish_toggle_button.style.display = 'inline';
		that.publish_toggle_button.value = BlorgCommentDisplay.publish_text;

		YAHOO.util.Dom.removeClass(that.container, 'blorg-comment-green');
		YAHOO.util.Dom.removeClass(that.container, 'blorg-comment-yellow');
		YAHOO.util.Dom.addClass(that.container, 'blorg-comment-red');

		that.updateStatus();
		that.setSensitivity(true);
	}

	BlorgCommentDisplay.xml_rpc_client.callProcedure('unpublish', callBack,
		[this.comment_id], ['int']);
}

// }}}
// {{{ togglePublished()

BlorgCommentDisplay.prototype.togglePublished = function()
{
	if (this.comment_status === BlorgCommentDisplay.STATUS_PUBLISHED) {
		this.unpublish();
	} else {
		this.publish();
	}
}

// }}}
// {{{ spam()

BlorgCommentDisplay.prototype.spam = function()
{
	this.setSensitivity(false);

	var that = this;
	function callBack(response)
	{
		that.comment_spam = true;

		that.approve_button.style.display = 'none';
		that.deny_button.style.display = 'none';
		that.publish_toggle_button.style.display = 'none';
		that.spam_toggle_button.value = BlorgCommentDisplay.not_spam_text;

		YAHOO.util.Dom.removeClass(that.container, 'blorg-comment-green');
		YAHOO.util.Dom.removeClass(that.container, 'blorg-comment-yellow');
		YAHOO.util.Dom.addClass(that.container, 'blorg-comment-red');

		that.updateStatus();
		that.setSensitivity(true);
	}

	BlorgCommentDisplay.xml_rpc_client.callProcedure('spam', callBack,
		[this.comment_id], ['int']);
}

// }}}
// {{{ notSpam()

BlorgCommentDisplay.prototype.notSpam = function()
{
	this.setSensitivity(false);

	var that = this;
	function callBack(response)
	{
		that.comment_spam = false;

		that.spam_toggle_button.value = BlorgCommentDisplay.spam_text;

		YAHOO.util.Dom.removeClass(that.container, 'blorg-comment-red');

		if (that.comment_status == BlorgCommentDisplay.STATUS_PENDING) {
			YAHOO.util.Dom.removeClass(that.container, 'blorg-comment-green');
			YAHOO.util.Dom.addClass(that.container, 'blorg-comment-yellow');
			that.approve_button.style.display = 'inline';
			that.deny_button.style.display = 'inline';
		} else {
			that.publish_toggle_button.style.display = 'inline';
			YAHOO.util.Dom.removeClass(that.container, 'blorg-comment-yellow');
			YAHOO.util.Dom.addClass(that.container, 'blorg-comment-green');
		}

		that.updateStatus();
		that.setSensitivity(true);
	}

	BlorgCommentDisplay.xml_rpc_client.callProcedure('notSpam', callBack,
		[this.comment_id], ['int']);
}

// }}}
// {{{ toggleSpam()

BlorgCommentDisplay.prototype.toggleSpam = function()
{
	if (this.comment_spam) {
		this.notSpam();
	} else {
		this.spam();
	}
}

// }}}
// {{{ setSensitivity()

BlorgCommentDisplay.prototype.setSensitivity = function(sensitive)
{
	this.approve_button.disabled        = !sensitive;
	this.deny_button.disabled           = !sensitive;
	this.publish_toggle_button.disabled = !sensitive;
	this.spam_toggle_button.disabled    = !sensitive;
	this.delete_button.disabled         = !sensitive;
}

// }}}
// {{{ updateStatus()

BlorgCommentDisplay.prototype.updateStatus = function()
{
	if (this.comment_spam) {
		BlorgCommentDisplay.setTextContent(this.status_container,
			' - ' + BlorgCommentDisplay.status_spam_text);
	} else {
		switch (this.comment_status) {
		case BlorgCommentDisplay.STATUS_UNPUBLISHED:
			BlorgCommentDisplay.setTextContent(this.status_container,
				' - ' + BlorgCommentDisplay.status_unpublished_text);

			break;

		case BlorgCommentDisplay.STATUS_PENDING:
			BlorgCommentDisplay.setTextContent(this.status_container,
				' - ' + BlorgCommentDisplay.status_pending_text);

			break;

		default:
			BlorgCommentDisplay.setTextContent(this.status_container, '');
			break;
		}
	}
}

// }}}
// {{{ deleteComment()

BlorgCommentDisplay.prototype.deleteComment = function()
{
	this.confirmation.style.display = 'none';

	var that = this;
	function callBack(response)
	{
		var attributes = { opacity: { to: 0 } };
		var anim = new YAHOO.util.Anim(that.container, attributes, 0.25,
			YAHOO.util.Easing.easeOut);

		anim.onComplete.subscribe(that.shrink, that, true);
		anim.animate();
	}

	BlorgCommentDisplay.xml_rpc_client.callProcedure('delete', callBack,
		[this.comment_id], ['int']);
}

// }}}
// {{{ shrink()

BlorgCommentDisplay.prototype.shrink = function()
{
	var anim = new YAHOO.util.Anim(this.container, { height: { to: 0 } },
		0.3, YAHOO.util.Easing.easeInStrong);

	anim.onComplete.subscribe(this.removeContainer, this, true);
	anim.animate();
}

// }}}
// {{{ removeContainer()

BlorgCommentDisplay.prototype.removeContainer = function()
{
	YAHOO.util.Event.purgeElement(this.container, true);
	this.container.parentNode.removeChild(this.container);
	delete this.container;
}

// }}}
// {{{ confirmDelete()

BlorgCommentDisplay.prototype.confirmDelete = function()
{
	this.setSensitivity(false);

	var parent_region = YAHOO.util.Dom.getRegion(this.delete_button);

	this.confirmation.style.display = 'block';

	var region = YAHOO.util.Dom.getRegion(this.confirmation);
	YAHOO.util.Dom.setXY(this.confirmation,
		[parent_region.right - (region.right - region.left),
		parent_region.top]);

	this.confirmation_cancel.focus();
}

// }}}
// {{{ cancelDelete()

BlorgCommentDisplay.prototype.cancelDelete = function()
{
	this.confirmation.style.display = 'none';
	this.setSensitivity(true);
}

// }}}
// {{{ static setTextContent()

BlorgCommentDisplay.setTextContent = function(element, text)
{
	if (element.innerText) {
		element.innerText = text;
	} else {
		element.textContent = text;
	}
}

// }}}
