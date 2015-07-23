/*jslint browser: true*/
/*global cmf_data, console, document, jQuery*/

var CM_Footnote = {};

/*
 * Inside this closure we use $ instead of jQuery in case jQuery is reinitialized again before document.ready()
 */
(function ($) {

    $(document).ready(function () {

        /*
         * Sharing Box
         */
        CM_Footnote.shareBox = function () {

            /*
             * We will assume that if we don't have the box we don't need this scripts
             */
            if ($(".cmf-social-box").length === 0) {
                return;
            }

            /*
             * We will assume that if we have one type of button we have them all
             */
            if ($(".twitter-share-button").length === 0) {
                return;
            }

            if (typeof (twttr) !== 'undefined' && typeof (twttr.widgets) !== 'undefined') {
                twttr.widgets.load();
            } else {
                $.getScript('//platform.twitter.com/widgets.js');
            }

            //Linked-in
            if (typeof (IN) !== 'undefined') {
                IN.parse();
            } else {
                $.getScript("//platform.linkedin.com/in.js");
            }

            (function () {
                var po = document.createElement('script');
                po.type = 'text/javascript';
                po.async = true;
                po.src = '//apis.google.com/js/plusone.js';
                var s = document.getElementsByTagName('script')[0];
                s.parentNode.insertBefore(po, s);
            })();

            (function (d, s, id) {
                if (typeof window.fbAsyncInit === 'undefined')
                {
                    window.fbAsyncInit = function () {
                        // Don't init the FB as it needs API_ID just parse the likebox
                        FB.XFBML.parse();
                    };

                    var js, fjs = d.getElementsByTagName(s)[0];
                    if (d.getElementById(id))
                        return;
                    js = d.createElement(s);
                    js.id = id;
                    js.src = "//connect.facebook.net/en_US/all.js";
                    fjs.parentNode.insertBefore(js, fjs);
                }
            }(document, 'script', 'facebook-jssdk'));
        };
        CM_Footnote.shareBox();

    });

}(jQuery));