create table BlorgGadgetSetting (
	gadget_instance integer not null
		references BlorgGadgetInstance(id) on delete cascade,

	name varchar(255) not null,
	value varchar(255) not null
);
