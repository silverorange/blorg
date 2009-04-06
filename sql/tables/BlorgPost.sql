create table BlorgPost (
	id serial,
	title varchar(255),
	shortname varchar(255),
	bodytext text,
	bodytext_filter varchar(50) not null default 'raw',
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

create index BlorgPost_instance_index on BlorgPost(instance);
create index BlorgPost_enabled_index on BlorgPost(enabled);
CREATE INDEX BlorgPost_instance_enabled_index ON BlorgPost(instance, enabled);
