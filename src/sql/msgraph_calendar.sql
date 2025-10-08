


/**


termin

    . id
    . begin
    . end
    . cal_typ

    . last_sync


**/



create table msgraph_calendar (
    id varchar(36),
    calendar_id varchar(255) not null,
    primary key(id, calendar_id),
    

    last_sync datetime default null,
    
   );