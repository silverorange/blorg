create table BlorgAuthor (
	id serial,
	instance integer not null references Instance(id),
	name varchar(255) not null,
	shortname varchar(255) not null,
	email varchar(255) null,
	displayorder integer not null default 0,
	description text null,
	bodytext text null,
	show boolean not null default true,
	primary key (id)
);
