create table BlorgPost (
	id serial,
	title varchar(255),
	shortname varchar(255),
	bodytext text,
	extended_bodytext text,
	createdate timestamp not null,
	modified_date timestamp,
	publish_date timestamp,
	comment_status integer not null default 0,
	author integer not null references BlorgAuthor(id),
	enabled boolean not null default false,
	-- media (binding with possible magical table),
	instance integer references Instance(id),
	primary key (id)
);
