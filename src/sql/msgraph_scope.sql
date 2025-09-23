delimiter ;

create table if not exists msgraph_scope (
    id varchar(255) primary key
) ;

insert ignore into msgraph_scope (id) values
('User.Read'),
('User.Read.All'),
('Group.Read.All'),
('Directory.Read.All'),
('Mail.Read'),
('Mail.Send'),
('Calendars.Read'),
('Calendars.ReadWrite'),
('Contacts.Read'),
('Contacts.ReadWrite'),
('Files.Read'),
('Files.Read.All'),
('Files.ReadWrite'),
('Files.ReadWrite.All'),
('Sites.Read.All'),
('Sites.Manage.All'),
('offline_access')
;

create table if not exists msgraph_webhook (
    id varchar(36) primary key,
    created timestamp default current_timestamp,
    last_checked timestamp default current_timestamp on update current_timestamp,
    processed timestamp null default null,
    method varchar(10),
    request longtext,
    server longtext,
    headers longtext,
    data longtext
) ;