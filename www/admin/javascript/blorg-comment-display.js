function BlorgCommentDisplay(id, comment_status, spam)
{
	this.id = id;
	this.comment_status = comment_status;
	this.spam = spam;

	var id_split = id.split('_', 2);
	this.comment_id = (id_split[1]) ? id_split[1] : id_split[0];

	this.initControls();
}

BlorgCommentDisplay.approve_text   = 'Approve';
BlorgCommentDisplay.deny_text      = 'Deny';
BlorgCommentDisplay.publish_text   = 'Publish';
BlorgCommentDisplay.unpublish_text = 'Unpublish';
BlorgCommentDisplay.spam_text      = 'Spam';
BlorgCommentDisplay.not_spam_text  = 'Not Spam';
BlorgCommentDisplay.delete_text    = 'Delete';

BlorgCommentDisplay.STATUS_PENDING     = 0;
BlorgCommentDisplay.STATUS_PUBLISHED   = 1;
BlorgCommentDisplay.STATUS_UNPUBLISHED = 2;

BlorgCommentDisplay.prototype.initControls = function()
{
	var controls_div = document.getElementById(this.id + '_controls');

	this.unpublish_button = document.createElement('input');
	this.unpublish_button.type = 'button';
	this.unpublish_button.value = BlorgCommentDisplay.unpublish_text;

	this.publish_button = document.createElement('input');
	this.publish_button.type = 'button';
	this.publish_button.value = BlorgCommentDisplay.publish_text;

	this.spam_button = document.createElement('input');
	this.spam_button.type = 'button';
	this.spam_button.value = BlorgCommentDisplay.spam_text;

	this.delete_button = document.createElement('input');
	this.delete_button.type = 'button';
	this.delete_button.value = BlorgCommentDisplay.delete_text;

	if (this.spam) {
		this.spam_button.value = BlorgCommentDisplay.not_spam_text;
	} else {
		switch (this.comment_status) {
		case BlorgCommentDisplay.STATUS_PENDING:
			this.publish_button.value   = BlorgCommentDisplay.approve_text;
			this.unpublish_button.value = BlorgCommentDisplay.deny_text;

			controls_div.appendChild(this.publish_button);
			controls_div.appendChild(this.unpublish_button);
			break;

		case BlorgCommentDisplay.STATUS_PUBLISHED:
			this.publish_button.value = BlorgCommentDisplay.unpublish_text;
			controls_div.appendChild(this.publish_button);
			break;

		case BlorgCommentDisplay.STATUS_UNPUBLISHED:
		default:
			controls_div.appendChild(this.publish_button);
			break;
		}
	}

	controls_div.appendChild(this.spam_button);
	controls_div.appendChild(this.delete_button);
}
