create table BlorgGadgetInstance (
	id serial,
	instance integer references Instance(id) on delete cascade,
	gadget varchar(255) not null,
	displayorder integer not null default 0,
	primary key (id)
);
