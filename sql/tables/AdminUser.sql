alter table AdminUser add show boolean not null default true;
alter table AdminUser add description text;
alter table AdminUser add bodytext text;
-- this may go in admin
alter table AdminUser add instance integer not null references Instance(id);

