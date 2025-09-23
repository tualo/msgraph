Ext.define('Tualo.MSGraph.lazy.Setup', {
    extend: 'Ext.panel.Panel',
    requires: [
        'Tualo.MSGraph.lazy.models.Setup',
        'Tualo.MSGraph.lazy.controller.Setup'
    ],
    alias: 'widget.msgraph_setup',
    controller: 'msgraph_setup',

    viewModel: {
        type: 'msgraph_setup'
    },
    listeners: {
        boxReady: 'onBoxReady'
    },

    layout: 'fit',
    /*
    bind:{
        title: '{ftitle}'
    },
    */
    items: [

        {
            hidden: false,
            xtype: 'panel',
            itemId: 'startpanel',
            layout: {
                type: 'vbox',
                align: 'center'
            },
            items: [
                {
                    xtype: 'component',
                    cls: 'lds-container-compact',
                    html: '<div class=" "><div class="blobs-container"><div class="blob gray"></div></div></div>'
                        + '<div><h3>Microsoft Zugriff</h3>'
                        + '<span>Einen Moment, die Daten werden geprüft.</span></div>'
                }
            ]
        },
        {
            hidden: true,
            xtype: 'panel',
            itemId: 'devicetoken',
            layout: {
                type: 'vbox',
                align: 'center'
            },
            items: [
                {
                    xtype: 'component',
                    cls: 'lds-container-compact',
                    bind: {
                        html: '{devicetokenhtml}'
                    }
                }
            ],
            dockedItems: [{
                xtype: 'toolbar',
                dock: 'bottom',
                items: [
                    {
                        xtype: 'button', text: 'Prüfen', bind: {
                            handler: 'checkToken'
                        }
                    }
                ]
            }],
        },
        {
            hidden: true,
            xtype: 'panel',
            itemId: 'user',
            layout: {
                type: 'vbox',
                align: 'center'
            },
            items: [
                {
                    xtype: 'component',
                    cls: 'lds-container-compact',
                    bind: {
                        html: '{userhtml}'
                    }
                }
            ],
            dockedItems: [{
                xtype: 'toolbar',
                dock: 'bottom',
                items: [
                    {
                        xtype: 'button', text: 'Erneuern', bind: {
                            handler: 'getDeviceToken'
                        }
                    }
                ]
            }]
        },
        {
            hidden: true,
            xtype: 'panel',
            itemId: 'apiconfig',
            layout: {
                type: 'vbox',
                align: 'center'
            },
            items: [
                {
                    xtype: 'form',
                    items: [
                        {
                            xtype: 'textfield',
                            fieldLabel: 'Client ID',
                            bind: {
                                value: '{client_id}'
                            }
                        },
                        {
                            xtype: 'textfield',
                            fieldLabel: 'Tenant ID',
                            bind: {
                                value: '{tenant_id}'
                            }
                        },
                        {
                            xtype: 'button',
                            text: 'Speichern',
                            bind: {
                                handler: 'saveConfig'
                            }
                        }
                    ]
                }
            ]
        }
    ],

});
