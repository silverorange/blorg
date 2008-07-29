create table BlorgAuthor (
	id serial,
	instance integer null references Instance(id),
	name varchar(255) not null,
	shortname varchar(255) not null,
	email varchar(255) null,
	displayorder integer not null default 0,
	description text null,
	bodytext text null,
	openid_server varchar(255) null,
	openid_delegate varchar(255) null,
	visible boolean not null default true,
	primary key (id)
);

create index BlorgAuthor_instance_index on BlorgAuthor(instance);
create index BlorgAuthor_visible_index on BlorgAuthor(visible);
