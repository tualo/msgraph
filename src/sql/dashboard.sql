delimiter;

insert
    ignore into dashboard_available_types (xtype, classname, description)
values
    (
        'tualodashboard_msgraph_state',
        'Tualo.MSGraph.lazy.dashboard.State',
        ''
    );
insert
    ignore into dashboard (id, title, dashboard_type, position, configuration)
values
    (
        'msgraph-8ece-11ee-aac7-ac1f6bd9bb0c',
        'MSGraph-Status',
        'tualodashboard_msgraph_state',
        10,
        '{}'
    );