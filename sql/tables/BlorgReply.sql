create table BlorgReply (
	id serial,
	post integer not null references BlorgPost(id) on delete cascade,
	author integer references BlorgAUthor(id),
	fullname varchar(255),
	link varchar(255),
	email varchar(255),
	bodytext text not null,
	status integer not null default 0,
	spam boolean not null default false,
	ip_address varchar(15),
	user_agent varchar(255),
	createdate timestamp not null,
	primary key (id)
);
