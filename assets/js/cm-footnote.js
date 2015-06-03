
(function ($) {

    $(document).ready(function () {

        /*
         * CUSTOM REPLACEMENTS
         */
        $.fn.add_new_replacement_row = function () {
            var articleRow, articleRowHtml, rowId;

            rowId = $(".custom-related-article").length;
            articleRow = $('<div class="custom-related-article"></div>');
            articleRowHtml = $('<input type="text" name="footnote_related_article_name[]" style="width: 40%" id="footnote_related_article_name" value="" placeholder="Name"><input type="text" name="footnote_related_article_url[]" style="width: 50%" id="footnote_related_article_url" value="" placeholder="http://"><a href="#javascript" class="footnote_related_article_remove">Remove</a>');
            articleRow.append(articleRowHtml);
            articleRow.attr('id', 'custom-related-article-' + rowId);

            $("#footnote-related-article-list").append(articleRow);
            return false;
        };

        $.fn.delete_replacement_row = function (row_id) {
            $("#custom-related-article-" + row_id).remove();
            return false;
        };

        /*
         * Added in 2.7.7 remove replacement_row
         */
        $(document).on('click', 'a.footnote_related_article_remove', function () {
            var $this = $(this), $parent;
            $parent = $this.parents('.custom-related-article').remove();
            return false;
        });

        /*
         * Added in 2.4.9 (shows/hides the explanations to the synonyms/abbreviations)
         */
        $(document).on('click showHideInit', '.cm-showhide-handle', function () {
            var $this = $(this), $parent, $content;

            $parent = $this.parent();
            $content = $this.siblings('.cm-showhide-content');

            if (!$parent.hasClass('closed'))
            {
                $content.hide();
                $parent.addClass('closed');
            }
            else
            {
                $content.show();
                $parent.removeClass('closed');
            }
        });

        $('.cm-showhide-handle').trigger('showHideInit');

        /*
         * CUSTOM REPLACEMENTS - END
         */

        if ($.fn.tabs) {
            $('#cmf_tabs').tabs({
                activate: function (event, ui) {
                    window.location.hash = ui.newPanel.attr('id').replace(/-/g, '_');
                },
                create: function (event, ui) {
                    var tab = location.hash.replace(/\_/g, '-');
                    var tabContainer = $(ui.panel.context).find('a[href="' + tab + '"]');
                    if (typeof tabContainer !== 'undefined' && tabContainer.length)
                    {
                        var index = tabContainer.parent().index();
                        $(ui.panel.context).tabs('option', 'active', index);
                    }
                }
            });
        }

        $('.cmf_field_help_container').each(function () {
            var newElement,
                    element = $(this);

            newElement = $('<div class="cmf_field_help"></div>');
            newElement.attr('title', element.html());

            if (element.siblings('th').length)
            {
            element.siblings('th').append(newElement);
            }
            else
            {
                element.siblings('*').append(newElement);
            }
            element.remove();
        });

        $('.cmf_field_help').tooltip({
            show: {
                effect: "slideDown",
                delay: 100
            },
            position: {
                my: "left top",
                at: "right top"
            },
            content: function () {
                var element = $(this);
                return element.attr('title');
            },
            close: function (event, ui) {
                ui.tooltip.hover(
                        function () {
                            $(this).stop(true).fadeTo(400, 1);
                        },
                        function () {
                            $(this).fadeOut("400", function () {
                                $(this).remove();
                            });
                        });
            }
        });

    });

})(jQuery);