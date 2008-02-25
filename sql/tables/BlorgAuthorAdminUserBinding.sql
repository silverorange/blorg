create table BlorgAuthorAdminUserBinding ( 
	author integer not null references BlorgAuthor(id),
	usernum integer not null references AdminUser(id),
	primary key (author, usernum)
);
