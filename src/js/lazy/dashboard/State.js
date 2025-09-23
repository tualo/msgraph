Ext.define('Tualo.MSGraph.lazy.dashboard.State', {
    requires: [
        // 'Ext.chart.CartesianChart'
    ],
    extend: 'Ext.dashboard.Part',
    alias: 'part.tualodashboard_msgraph_state',


    viewTemplate: {
        title: 'Microsoft Verbindung',


        items: [
            {
                xtype: 'panel',
                layout: 'fit',
                items: [],
                listeners: {
                    boxready: function (me) {
                        var elem = Ext.create('Tualo.MSGraph.lazy.Setup');
                        console.log(me);
                        me.add(elem);
                    }
                }
            }
        ]
    }
});