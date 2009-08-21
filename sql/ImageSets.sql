-- Files
insert into imageset (shortname, obfuscate_filename) values ('files', false);

insert into ImageDimension (image_set, default_type, shortname, title, max_width, max_height, crop, dpi, quality, strip, interlace, resize_filter)
	select id, 1, 'thumb', 'Thumb', 100, 100, true, 72, 85, true, false, null from ImageSet where shortname = 'files';

insert into ImageDimension (image_set, default_type, shortname, title, max_width, max_height, crop, dpi, quality, strip, interlace, resize_filter)
	select id, 1, 'pinky', 'Pinky', 48, 48, true, 72, 85, true, false, null from ImageSet where shortname = 'files';

insert into ImageDimension (image_set, default_type, shortname, title, max_width, max_height, crop, dpi, quality, strip, interlace, resize_filter)
	select id, 1, 'original', 'Original', null, null, false, 72, 85, true, false, 'FILTER_BOX' from ImageSet where shortname = 'files';

insert into ImageDimension (image_set, default_type, shortname, title, max_width, max_height, crop, dpi, quality, strip, interlace, resize_filter)
	select id, 1, 'small', 'Small', 400, null, false, 72, 85, true, false, null from ImageSet where shortname = 'files';
