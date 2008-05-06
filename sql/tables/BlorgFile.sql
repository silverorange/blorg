create table BlorgFile (
	id serial,
	post integer references BlorgPost(id) on delete cascade,
	form_unique_id varchar(25),
	image integer references Image(id) on delete cascade,
	filename varchar(255),
	filesize integer,
	mime_type varchar(50),
	description text,
	enabled boolean not null default true,
	createdate timestamp not null,
	primary key (id)
);
