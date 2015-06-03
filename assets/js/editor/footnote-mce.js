(function () {
    tinymce.create("tinymce.plugins.Footnote", {
        init: function (ed, url) {

            ed.addButton('cmf_exclude', {
                title: 'Exclude from CM Footnotes',
                image: url + '/icon.png',
                onclick: function () {
                    ed.focus();
                    ed.selection.setContent('[footnote_exclude]' + ed.selection.getContent() + '[/footnote_exclude]');
                }
            });

            ed.addButton('cmf_parse', {
                title: 'Parse with CM Footnotes',
                image: url + '/icon.png',
                onclick: function () {
                    ed.focus();
                    ed.selection.setContent('[cm_footnote_parse]' + ed.selection.getContent() + '[/cm_footnote_parse]');
                }
            });

        },
        getInfo: function () {
            return{
                longname: "CM Footnotes",
                author: "CreativeMinds",
                authorurl: "https://www.cminds.com/",
                infourl: "https://www.cminds.com/",
                version: "2.0"
            };
        },
        createControl: function (n, cm) {
            return null;
        }
    });

    tinymce.PluginManager.add("cmf_footnote", tinymce.plugins.Footnote);
}());