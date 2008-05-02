create table BlorgMedia (
	id serial,
	post integer not null references BlorgPost(id) on delete cascade,
	image integer references Image(id) on delete cascade,
	filename varchar(255),
	filesize integer,
	mime_type varchar(50),
	description text,
	enabled boolean not null default true,
	createdate timestamp not null,
	primary key (id)
);
