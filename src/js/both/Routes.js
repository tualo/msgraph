Ext.define('Tualo.routes.MSGraph.Setup', {
    statics: {
        load: async function () {
            return [
                {
                    name: 'msgraph/setup',
                    path: '#msgraph/setup'
                }
            ]
        }
    },
    url: 'msgraph/setup',
    handler: {
        action: function () {

            let mainView = Ext.getApplication().getMainView(),
                stage = mainView.getComponent('dashboard_dashboard').getComponent('stage'),
                component = null,
                cmp_id = 'msgraph_setup';
            component = stage.down(cmp_id);
            if (component) {
                stage.setActiveItem(component);
            } else {
                Ext.getApplication().addView('Tualo.MSGraph.lazy.Setup', {

                });
            }


        },
        before: function (action) {

            action.resume();
        }
    }
});