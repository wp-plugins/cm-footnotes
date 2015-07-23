/*
 Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
 For licensing, see LICENSE.html or http://ckeditor.com/license
 */

/**
 * @file Sample plugin for CKEditor.
 */
(function () {
    CKEDITOR.plugins.add('cmf_footnote',
            {
                init: function (editor)
                {
                    var a = {
                        exec: function (editor) {
                            var selection = editor.getSelection();
                            var text = selection.getSelectedText();
                            var nodeHtml = selection.getStartElement();
                            console.log(nodeHtml);
                            console.log(nodeHtml.innerHTML);
                            nodeHtml.setText(nodeHtml.getText().replace(text, '[footnote_exclude]' + text + '[/footnote_exclude]'));
                        }
                    };

                    editor.addCommand('cmf_exclude_cmd', a);

                    editor.ui.addButton('cmf_exclude',
                            {
                                label: 'Exclude from CM Footnotes',
                                command: 'cmf_exclude_cmd',
                                toolbar: 'links',
                                icon: this.path + '../icon.png'
                            });

                    var b = {
                        exec: function (editor) {
                            var selection = editor.getSelection();
                            var text = selection.getSelectedText();
                            var nodeHtml = selection.getStartElement();
                            console.log(nodeHtml);
                            console.log(nodeHtml.innerHTML);
                            nodeHtml.setText(nodeHtml.getText().replace(text, '[cm_footnote_parse]' + text + '[/cm_footnote_parse]'));
                        }
                    };

                    editor.addCommand('cmf_parse_cmd', b);

                    editor.ui.addButton('cmf_parse',
                            {
                                label: 'Parse with CM Footnotes',
                                command: 'cmf_parse_cmd',
                                toolbar: 'links',
                                icon: this.path + '../icon.png'
                            });
                }
            });
})();
