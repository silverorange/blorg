create table BlorgPost (
	id serial,
	title varchar(255),
	shortname varchar(255),
	bodytext text,
	extended_bodytext text,
	createdate timestamp not null,
	modified_date timestamp,
	post_date timestamp,
	reply_status integer not null default 0,
	author integer not null references AdminUser(id),
	enabled boolean not null default false,
	-- media (binding with possible magical table),
	instance integer references Instance(id),
	primary key (id)
);
