create table BlorgAuthor (
	id serial,
	instance integer null references Instance(id),
	name varchar(255) not null,
	shortname varchar(255) not null,
	email varchar(255) null,
	displayorder integer not null default 0,
	description text null,
	bodytext text null,
	visible boolean not null default true,
	primary key (id)
);
