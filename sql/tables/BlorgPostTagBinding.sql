create table BlorgPostTagBinding (
	post integer not null references BlorgPost(id),
	tag integer not null references BlorgTag(id),
	primary key(post, tag)
);
