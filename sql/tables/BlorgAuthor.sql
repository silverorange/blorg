create table BlorgAuthor (
	id serial,
	instance integer not null references Instance(id),
	name varchar(255),
	shortname varchar(255),
	email varchar(255),
	displayorder integer not null default 0,
	description text,
	bodytext text,
	show boolean not null default true,
	primary key (id)
);
