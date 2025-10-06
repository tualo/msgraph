Ext.define('Tualo.MSGraph.lazy.models.Setup', {
  extend: 'Ext.app.ViewModel',
  alias: 'viewmodel.msgraph_setup',
  data: {
    message: 'OK',

    state: 1,
    devicetoken_message: -1,
    devicetoken_expires_in: -1,
    devicetoken_interval: 1000,
    devicetoken_user_code: -1,
    devicetoken_verification_uri: '',

    displayName: '',
    email: ''
  },
  formulas: {
    ftitle: function (get) {
      let txt = 'MS Login';
      return txt;
    },

    info: function (get) {
      let txt = 'MS Login';
      return get('message');
    },


    devicetokenhtml: function (get) {
      return [
        '<div class=" ">',
        '<div class="blobs-container">',
        '<div class="blob yellow">',
        '</div>',
        '</div>',
        '</div>',
        '<div>',
        '<h3>Microsoft Zugriff</h3>',
        '<span>',
        'Bitte geben Sie den Zugriff frei.',
        '</span>',
        '<br>',
        '<a href="', get('devicetoken_verification_uri'), '" target="_blank" style="font-size: 1.5em;line-height: 1.5em;">',
        get('devicetoken_verification_uri'),
        '</a>',
        '<br>',
        '<span>',
        'Der Code für die Freigabe lautet: ',
        '<br>',
        '<strong style="font-size: 2em;line-height: 2em;">',
        get('devicetoken_user_code'),
        '</strong>',
        '</span>',
        '</div>'
      ].join('')
    },



    userhtml: function (get) {
      return [
        '<div class=" ">',
        '<div class="blobs-container">',
        '<div class="blob green">',
        '</div>',
        '</div>',
        '</div>',
        '<div>',
        '<h3>Microsoft Zugriff</h3>',
        '<span>',
        '<a href="ms-word:ofe|u|https://tualo-my.sharepoint.com/personal/thomas_hoffmann_tualo_io/_layouts/15/Doc.aspx?sourcedoc=%7B06A1ADDC-6C5D-45F2-8FC5-A1C6D817ECF9%7D&file=Document.docx&action=default&mobileredirect=true" target="_blank" style="font-size: 1.5em;line-height: 1.5em;">',
        'Dokument öffnen',
        '</a>',
        '<br>',
        'Sie sind angemeldet als',
        '</span>',
        '<br>',
        '<strong style="font-size: 2em;line-height: 2em;">',
        get('displayName'),
        '</strong>',
        '<br>',
        '<strong style="font-size: 1.2em;line-height: 1.2em;">',
        get('mail'),
        '</strong>',
        '</div>'
      ].join('')
    },

  },
  stores: {


  }
});
