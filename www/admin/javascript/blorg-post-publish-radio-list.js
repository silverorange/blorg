function BlorgPostPublishRadioList(list_name, publish_now_id,
	publish_at_id, choose_date_text)
{
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
			this.choose_at_option.parentNode.style.display = 'none';

			this.anchor = document.createElement('a');
			this.anchor.href = '#';
			this.anchor.title = choose_date_text;
			this.anchor.innerHTML = choose_date_text;
			YAHOO.util.Event.addListener(this.anchor, 'click',
			function(e, list)
			{
				YAHOO.util.Event.preventDefault(e);
				list.toggle();
			}, this);

			var o = document.getElementById(list_name + '_' + publish_now_id);
			o.parentNode.appendChild(this.anchor);
		}
	}
}

BlorgPostPublishRadioList.prototype.toggle = function()
{
	this.choose_at_option.parentNode.style.display = 'block';
	this.anchor.style.display = 'none';
	this.choose_at_option.checked = true;
	this.updateSensitivity();
}

BlorgPostPublishRadioList.prototype.updateSensitivity = function()
{
	var entries = [
		document.getElementById('publish_date_year'),
		document.getElementById('publish_date_month'),
		document.getElementById('publish_date_day'),
		document.getElementById('publish_date_time_entry_hour'),
		document.getElementById('publish_date_time_entry_minute'),
		document.getElementById('publish_date_time_entry_am_pm'),
	];

	for (var i = 0; i < entries.length; i++) {
		var entry = entries[i];

		if (this.choose_at_option.checked) {
			YAHOO.util.Dom.removeClass(entry, 'swat-insensitive');
			entry.disabled = false;
		} else {
			YAHOO.util.Dom.addClass(entry, 'swat-insensitive');
			entry.disabled = true;
		}
	}
}
