function BlorgPublishRadioTable(list_name, publish_now_id,
	publish_at_id, choose_date_text)
{
	this.publish_date = window[list_name + '_date_obj'];

	var radio_list = document.getElementsByName(list_name);
	if (radio_list) {
		this.choose_at_option = document.getElementById(
			list_name + '_' + publish_at_id);

		for (var i = 0; i < radio_list.length; i++) {
			YAHOO.util.Event.addListener(radio_list[i], 'change',
				this.updateSensitivity, this, true);

			YAHOO.util.Event.addListener(radio_list[i], 'keyup',
				this.updateSensitivity, this, true);
		}

		this.updateSensitivity();

		if (document.getElementById(list_name + '_' + publish_now_id)) {
			var choose_at_tr = this.choose_at_option.parentNode.parentNode;
			choose_at_tr.style.display = 'none';

			this.anchor = document.createElement('span');
			this.anchor.className = 'edit-publish-date-link';

			this.anchor.appendChild(document.createTextNode(' ('));

			var a_tag = document.createElement('a');
			a_tag.href = '#';
			a_tag.title = choose_date_text;
			a_tag.innerHTML = choose_date_text;
			YAHOO.util.Event.addListener(a_tag, 'click',
			function(e, list)
			{
				YAHOO.util.Event.preventDefault(e);
				list.toggle();
			}, this);

			var publish_now_td = document.getElementById(
				list_name + '_' + publish_now_id + '_label');

			this.anchor.appendChild(a_tag);
			this.anchor.appendChild(document.createTextNode(')'));
			publish_now_td.appendChild(this.anchor);
		}
	}
}

BlorgPublishRadioTable.prototype.toggle = function()
{
	this.choose_at_option.parentNode.parentNode.style.display = 'table-row';
	this.anchor.style.display = 'none';
	this.choose_at_option.checked = true;
	this.updateSensitivity();
}

BlorgPublishRadioTable.prototype.updateSensitivity = function()
{
	this.publish_date.setSensitivity(this.choose_at_option.checked);
}
