create table BlorgPostTagBinding (
	post integer not null references BlorgPost(id) on delete cascade,
	tag integer not null references BlorgTag(id) on delete cascade,
	primary key(post, tag)
);
