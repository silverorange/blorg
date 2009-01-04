function BlorgFlickrJsonGadget(json_results)
{
	var div = document.getElementById(BlorgFlickrJsonGadget.div);
	var limit = BlorgFlickrJsonGadget.limit;
	var i = 0;

	while(json_results.items[i]) {
		var element = json_results.items[i];
		var image_uri = this.getImageUri(element.media.m);

		var link = document.createElement('a');
		link.href = element.link;
		link.style.background = "url('" + image_uri  + "') no-repeat";
		link.style.display = 'block';

		var img = document.createElement('img');
		img.src = image_uri;
		img.title = element.title;
		img.alt = '';

		if (BlorgFlickrJsonGadget.size == 'square') {
			link.style.margin = '2px';
			link.style.cssFloat = 'left';
			link.style.styleFloat = 'left';
		}

		link.appendChild(img);
		div.appendChild(link);

		if (limit > 0 && (i + 1) == limit)
			break;

		i++;
	}

	if (BlorgFlickrJsonGadget.size == 'square') {
		var clear = document.createElement('div');
		clear.style.clear = 'left';
		div.appendChild(clear);
	}
}

BlorgFlickrJsonGadget.prototype.getImageUri = function(base_uri)
{
	var base_uri = base_uri.slice(0, -6);

	switch (BlorgFlickrJsonGadget.size) {
	case 'thumbnail' :
		return base_uri + '_t.jpg';
	case 'medium' :
		return base_uri + '_m.jpg';
	case 'small' :
		return base_uri + '.jpg';
	case 'big' :
		return base_uri + '_b.jpg';
	default :
		return base_uri + '_s.jpg';
	}
}

BlorgFlickrJsonGadget.limit = 0;
BlorgFlickrJsonGadget.div = null;
BlorgFlickrJsonGadget.size = 'square';

function jsonFlickrFeed(json_results) {
	new BlorgFlickrJsonGadget(json_results);
}
