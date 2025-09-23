Ext.define('Tualo.MSGraph.lazy.controller.Setup', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.msgraph_setup',

    onBoxReady: function () {
        let me = this;
        setTimeout(me.queryUser.bind(me), 1000)
    },

    queryUser: async function () {
        let me = this;
        let response = await fetch('./msgraph/setup/user');
        let data = await response.json();
        if (data.success) {
            me.getView().getComponent('startpanel').hide();
            me.getView().getComponent('devicetoken').hide();
            me.getView().getComponent('apiconfig').hide();
            me.getView().getComponent('user').show();


            me.getViewModel().set('displayName', data.response.displayName)
            me.getViewModel().set('mail', data.response.mail)

        } else {
            if (data.error == "Lifetime validation failed, the token is expired.") {
                setTimeout(me.getDeviceToken.bind(me), 2000)
            }

            if (data.error == "no access token") {
                setTimeout(me.getDeviceToken.bind(me), 2000)
            }

            if (data.error == "no setup") {
                setTimeout(me.getDeviceToken.bind(me), 1000)
            }

            if (data.error == "no setup found!") {

                me.getView().getComponent('startpanel').hide();
                me.getView().getComponent('user').hide();
                me.getView().getComponent('devicetoken').hide();
                me.getView().getComponent('apiconfig').show();

                // alert('No setup found! Please contact your administrator.');
                // setTimeout(me.getDeviceToken.bind(me),10000)
            }
        }
    },

    saveConfig: async function () {
        let me = this;
        let response = await fetch('./microsoft-mail/setup/apiconfig', {
            method: "POST",
            body: JSON.stringify({
                client_id: me.getViewModel().get('client_id'),
                tenant_id: me.getViewModel().get('tenant_id')
            })
        });

        let data = await response.json();
        if (data.success) {
            me.getView().getComponent('startpanel').show();
            me.getView().getComponent('user').hide();
            me.getView().getComponent('apiconfig').hide();
            me.getView().getComponent('devicetoken').hide();
            setTimeout(me.onBoxReady.bind(me), 1000)
        }
    },

    getDeviceToken: async function () {
        let me = this;

        me.getView().getComponent('startpanel').hide();
        me.getView().getComponent('user').hide();
        me.getView().getComponent('apiconfig').hide();
        me.getView().getComponent('devicetoken').show();

        let response = await fetch('./msgraph/setup/user/devicecode');
        let data = await response.json();
        console.log(data)
        if (data.success) {
            let response = data.response

            me.getViewModel().set('devicetoken_verification_uri', response.verification_uri)
            me.getViewModel().set('devicetoken_device_code', response.device_code)
            me.getViewModel().set('devicetoken_message', response.message)
            me.getViewModel().set('devicetoken_expires_in', parseInt(response.expires_in))
            me.getViewModel().set('devicetoken_interval', parseInt(response.interval))
            me.getViewModel().set('devicetoken_user_code', response.user_code)
            me.deviceCodeChecker = setTimeout(
                me.checkToken.bind(me),
                me.getViewModel().get('devicetoken_interval') * 1000 + 10
            )
        }
    },

    checkToken: async function () {
        let me = this;
        let response = await fetch('./msgraph/setup/user/checktoken', {
            method: "POST",
            body: JSON.stringify({ device_code: me.getViewModel().get('devicetoken_device_code') })
        });

        let data = await response.json();
        if (data.success) {
            me.queryUser();
        } else {


            /*
            me.deviceCodeChecker = setTimeout(
                me.checkToken.bind(me),
                me.getViewModel().get('devicetoken_interval') * 1000 + 10
            )
            */
        }
    }
});
