create table BlorgReply (
	id serial,
	post integer not null references BlorgPost(id) on delete cascade,
	author integer references AdminUser(id),
	fullname varchar(255),
	link varchar(255),
	email varchar(255),
	bodytext text not null,
	show boolean not null default true,
	approved boolean not null default true,
	spam boolean not null default false,
	ip_address varchar(15),
	user_agent varchar(255),
	createdate timestamp not null,
	primary key (id)
);
