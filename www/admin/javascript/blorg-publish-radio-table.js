function BlorgPublishRadioTable(list_name, publish_now_id,
	publish_at_id, choose_date_text)
{
	this.publish_date = window[list_name + '_date_obj'];

	var radio_list = document.getElementsByName(list_name);
	if (radio_list.length > 0) {
		this.publish_at_option = document.getElementById(
			list_name + '_' + publish_at_id);

		for (var i = 0; i < radio_list.length; i++) {
			YAHOO.util.Event.addListener(radio_list[i], 'change',
				this.updateSensitivity, this, true);

			YAHOO.util.Event.addListener(radio_list[i], 'keyup',
				this.updateSensitivity, this, true);
		}

		this.updateSensitivity();

		if (this.publish_at_option && !this.publish_at_option.checked) {
			this.publish_at_tr = this.publish_at_option.parentNode.parentNode;
			this.publish_at_tr.style.display = 'none';

			this.edit_span = document.createElement('span');
			this.edit_span.className = 'edit-publish-date-link';

			this.edit_span.appendChild(document.createTextNode(' ('));

			var a_tag = document.createElement('a');
			a_tag.href = '#';
			a_tag.title = choose_date_text;
			a_tag.innerHTML = choose_date_text;
			YAHOO.util.Event.addListener(a_tag, 'click',
			function(e, list)
			{
				YAHOO.util.Event.preventDefault(e);
				list.showPublishAt();
			}, this);

			this.edit_span.appendChild(a_tag);
			this.edit_span.appendChild(document.createTextNode(')'));

			var publish_now_td = document.getElementById(
				list_name + '_' + publish_now_id + '_label');

			publish_now_td.appendChild(this.edit_span);
		}
	}
}

BlorgPublishRadioTable.prototype.showPublishAt = function()
{
	this.publish_at_tr.style.display = 'table-row';
	this.publish_at_option.checked = true;
	var in_attributes = { opacity: { from: 0, to: 1 } };
	var in_animation = new YAHOO.util.Anim(this.publish_at_tr, in_attributes,
		0.5, YAHOO.util.Easing.easeIn);

	var out_attributes = { opacity: { from: 1, to: 0 } };
	var out_animation = new YAHOO.util.Anim(this.edit_span, out_attributes,
		0.25, YAHOO.util.Easing.easeOut);

	out_animation.onComplete.subscribe(function(e)
	{
		this.edit_span.style.display = 'none';
	}, this, true);

	in_animation.animate();
	out_animation.animate();

	this.updateSensitivity();
}

BlorgPublishRadioTable.prototype.updateSensitivity = function()
{
	this.publish_date.setSensitivity(this.publish_at_option.checked);
}
