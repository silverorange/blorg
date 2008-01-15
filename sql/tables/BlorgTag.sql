create table BlorgTag (
	id serial,
	title varchar(255),
	shortname varchar(255),
	createdate timestamp not null,
	instance integer not nul lreferences Instance(id),
	primary key (id)
);
