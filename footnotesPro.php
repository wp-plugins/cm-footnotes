<?php

class FootnoteException extends Exception
{

}
;

class CMF_Pro
{
    protected static $filePath = '';
    protected static $cssPath = '';
    protected static $jsPath = '';
    public static $lastQueryDetails = array();
    public static $calledClassName;

    const DISPLAY_NOWHERE = 0;
    const DISPLAY_EVERYWHERE = 1;
    const DISPLAY_ONLY_ON_PAGES = 2;
    const DISPLAY_EXCEPT_ON_PAGES = 3;
    const PAGE_YEARLY_OFFER = 'https://www.cminds.com/store/cm-wordpress-plugins-yearly-membership/';

    public static function init()
    {
        global $cmf_isLicenseOk;

        self::setupConstants();

        self::includeFiles();

        self::initFiles();

        self::addOptions();

        if( empty(self::$calledClassName) )
        {
            self::$calledClassName = __CLASS__;
        }

        $file = basename(__FILE__);
        $folder = basename(dirname(__FILE__));
        $hook = "in_plugin_update_message-{$folder}/{$file}";
        add_action($hook, array(self::$calledClassName, 'cmf_warn_on_upgrade'));

        self::$filePath = plugin_dir_url(__FILE__);
        self::$cssPath = self::$filePath . 'assets/css/';
        self::$jsPath = self::$filePath . 'assets/js/';

        add_action('init', array(self::$calledClassName, 'cmf_create_post_types'));

        add_action('admin_menu', array(self::$calledClassName, 'cmf_admin_menu'));
        add_action('admin_head', array(self::$calledClassName, 'addRicheditorButtons'));

        add_action('admin_enqueue_scripts', array(self::$calledClassName, 'cmf_footnote_admin_settings_scripts'));
        add_action('admin_enqueue_scripts', array(self::$calledClassName, 'cmf_footnote_admin_edit_scripts'));

        add_action('restrict_manage_posts', array(self::$calledClassName, 'cmf_restrict_manage_posts'));

        add_action('wp_print_styles', array(self::$calledClassName, 'cmf_footnote_css'));
        add_action('admin_notices', array(self::$calledClassName, 'cmf_footnote_admin_notice_wp33'));
        add_action('admin_notices', array(self::$calledClassName, 'cmf_footnote_admin_notice_mbstring'));
        add_action('admin_notices', array(self::$calledClassName, 'cmf_footnote_admin_notice_client_pagination'));
        add_action('admin_print_footer_scripts', array(self::$calledClassName, 'cmf_quicktags'));
        add_action('add_meta_boxes', array(self::$calledClassName, 'cmf_RegisterBoxes'));
        add_action('save_post', array(self::$calledClassName, 'cmf_save_postdata'));
        add_action('update_post', array(self::$calledClassName, 'cmf_save_postdata'));

        add_filter('cmf_add_properties_metabox', array(self::$calledClassName, 'footnoteProperies'));

        add_action('wp_ajax_cmf_add_new_footnote', array(self::$calledClassName, 'ajaxAddNewFootnote'));

        add_filter('cmf_settings_footnote_tab_content_after', 'cminds_cmf_settings_footnote_tab_content_after');

        /*
         * FILTERS
         */
        add_filter('get_the_excerpt', array(self::$calledClassName, 'cmf_disable_parsing'), 1);
        add_filter('wpseo_opengraph_desc', array(self::$calledClassName, 'cmf_reenable_parsing'), 1);
        /*
         * Make sure parser runs before the post or page content is outputted
         */
        add_filter('the_content', array(self::$calledClassName, 'cmf_footnote_parse'), 9999);


        add_filter('cm_footnote_parse_end', array(self::$calledClassName, 'outputFootnotes'));

        /*
         * It's a custom filter which can be applied to create the footnotes
         */
        add_filter('cm_footnote_parse', array(self::$calledClassName, 'cmf_footnote_parse'), 9999, 2);
        add_filter('the_title', array(self::$calledClassName, 'cmf_footnote_addTitlePrefix'), 10006, 2);

        if( get_option('cmf_footnoteShowShareBoxTermPage') == 1 )
        {
            add_filter('cmf_footnote_term_after_content', array(self::$calledClassName, 'cmf_footnoteAddShareBox'));
        }


        /*
         * Filter for the BuddyPress record
         */
        add_filter('bp_blogs_record_comment_post_types', array(self::$calledClassName, 'cmf_bp_record_my_custom_post_type_comments'));

        add_filter('cmf_is_footnote_clickable', array(self::$calledClassName, 'isFootnoteClickable'));

        /*
         * Footnote Content ADD
         */
        add_filter('cmf_footnote_content_add', array(self::$calledClassName, 'cmf_footnote_parse_strip_shortcodes'), 4, 2);
        add_filter('cmf_footnote_content_add', array(self::$calledClassName, 'addFootnoteDescriptionFold'), 1000, 2);
        add_filter('cmf_footnote_content_add', array(self::$calledClassName, 'addEditlinkToFootnote'), 2000, 2);

        /*
         * "Normal" Footnote Content
         */
        add_filter('cmf_term_footnote_content', array(self::$calledClassName, 'getTheFootnoteContentBase'), 10, 2);
        add_filter('cmf_term_footnote_content', array(self::$calledClassName, 'cmf_footnote_parse_strip_shortcodes'), 20, 2);

        add_action('admin_footer', array(self::$calledClassName, 'addFootnoteThickbox'), 100);
        /*
         * SHORTCODES
         */
        add_shortcode('cm_footnote_parse', array(self::$calledClassName, 'cm_footnote_parse'));
        add_shortcode('cm_custom_footnote', '__return_empty_string');
        add_shortcode('cm_footnotes', '__return_empty_string');
    }

    /**
     * Include the files
     */
    public static function includeFiles()
    {
        do_action('cmf_include_files_before');

        include_once CMF_PLUGIN_DIR . "related.php";
        include_once CMF_PLUGIN_DIR . "functions.php";

        do_action('cmf_include_files_after');
    }

    /**
     * Initialize the files
     */
    public static function initFiles()
    {
        do_action('cmf_init_files_before');

        CMF_Related::init();

        do_action('cmf_init_files_after');
    }

    /**
     * Adds options
     */
    public static function addOptions()
    {
        /*
         * General settings
         */
        add_option('cmf_footnoteOnMainQuery', 1); //Show on Main Query only
        add_option('cmf_footnoteOnPages', 1); //Show on Pages?
        add_option('cmf_footnoteOnPosts', 1); //Show on Posts?
        add_option('cmf_footnoteOnFootnote', 1); //Show on Footnote Pages?
        add_option('cmf_footnoteID', -1); //The ID of the main Footnote Page
        add_option('cmf_footnoteOnlySingle', 0); //Show on Home and Category Pages or just single post pages?
        add_option('cmf_footnoteFirstOnly', 0); //Search for all occurances in a post or only one?
        add_option('cmf_footnoteOnlySpaceSeparated', 1); //Search only for words separated by spaces
        add_option('cmf_script_in_footer', 0); //Place the scripts in the footer not the header
        add_option('cmf_footnoteOnPosttypes', array('post', 'page', 'footnote')); //Default post types where the terms are highlighted


        add_option('cmf_disable_metabox_all_post_types', 0); //show disable metabox for all post types
        /*
         * Footnote settings
         */
        add_option('cmf_footnoteHeaderAnchorTitle', 'Title');
        add_option('cmf_footnoteHeaderDescription', 'Description');
        /*
         * Footnote styling
         */
        add_option('cmf_footnoteSymbolStyle', 'numbers');
        add_option('cmf_footnoteSymbolSize', '10px');

        add_option('cmf_footnoteSymbolLinkAnchorSize', '12px');

        add_option('cmf_footnoteTitleSize', '15px');

        add_option('cmf_footnoteDescriptionSize', '15px');
        /*
         * Footnote page styling
         */
        add_option('cmf_footnoteShowShareBox', 0); //Show/hide the Share This box on top of the Footnote Index Page
        add_option('cmf_footnoteShowShareBoxTermPage', 0); //Show/hide the Share This box on top of the Footnote Term Page
        add_option('cmf_footnoteShowShareBoxLabel', 'Share This'); //Label of the Sharing Box on the Footnote Index Page
        add_option('cmf_footnoteDescLength', 300); //Limit the length of the definision shown on the Footnote Index Page
        add_option('cmf_footnoteDiffLinkClass', 0); //Use different class to style footnote list
        add_option('cmf_footnoteListTermLink', 0); //Remove links from footnote index to footnote page
        add_option('cmf_index_letters', array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'));
        add_option('cmf_footnoteDesc', 0); // Display description in footnote list
        add_option('cmf_footnoteDescExcerpt', 0); // Display excerpt in footnote list
        add_option('cmf_footnoteServerSidePagination', 0); //paginate server side or client side (with alphabetical index)
        add_option('cmf_perPage', 0); //pagination on "footnote page" withing alphabetical navigation
        add_option('cmf_footnoteRunApiCalls', 0); //exclude the API calls from the footnote main page
        add_option('cmf_index_includeNum', 1);
        add_option('cmf_index_includeAll', 1);
        add_option('cmf_index_allLabel', 'ALL');
        add_option('cmf_footnote_backLinkText', '&laquo; Back to Footnote Index');
        add_option('cmf_footnote_backLinkBottomText', '&laquo; Back to Footnote Index');
        /*
         * Related articles
         */
        add_option('cmf_footnote_showRelatedArticles', 1);
        add_option('cmf_footnote_showRelatedArticlesCount', 5);
        add_option('cmf_footnote_showRelatedArticlesFootnoteCount', 5);
        add_option('cmf_footnote_showRelatedArticlesTitle', 'Related Articles:');
        add_option('cmf_footnote_showRelatedArticlesPostTypesArr', array('post', 'page', 'footnote'));
        add_option('cmf_footnote_relatedArticlesPrefix', 'Footnote: ');
        /*
         * Synonyms
         */
        add_option('cmf_footnote_addSynonyms', 1);
        add_option('cmf_footnote_addSynonymsTitle', 'Synonyms: ');
        add_option('cmf_footnote_addSynonymsFootnote', 0);
        /*
         * Referral
         */
        add_option('cmf_footnoteReferral', false);
        add_option('cmf_footnoteAffiliateCode', '');
        /*
         * Footnote term
         */
        add_option('cmf_footnoteBeforeTitle', ''); //Text which shows up before the title on the term page
        /*
         * Footnote content
         */
        add_option('cmf_footnote', 1); //Use footnotes on footnote items?
        add_option('cmf_footnoteStripShortcode', 0); //Strip the shortcodes from footnote page before placing the footnote?
        add_option('cmf_footnoteLimitFootnote', 0); // Limit the footnote length  ?
        add_option('cmf_footnoteTermDetailsLink', 'Term details'); // Label of the link to term's details
        add_option('cmf_footnoteExcerptHover', 0); //Search for all occurances in a post or only one?
        add_option('cmf_footnoteProtectedTags', 1); //Aviod the use of Footnote in Protected tags?
        add_option('cmf_footnoteCaseSensitive', 0); //Case sensitive?
        /*
         * Footnote link
         */
        add_option('cmf_footnoteRemoveCommentsTermPage', 1); //Remove the comments from term page
        add_option('cmf_footnoteInNewPage', 0); //In New Page?
        add_option('cmf_footnoteTermLink', 0); //Remove links to footnote page
        add_option('cmf_showTitleAttribute', 0); //show HTML title attribute
        /*
         * Footnote styling
         */
        add_option('cmf_footnoteIsClickable', 1);
        add_option('cmf_footnoteLinkUnderlineStyle', 'dotted');
        add_option('cmf_footnoteLinkUnderlineWidth', 1);
        add_option('cmf_footnoteLinkUnderlineColor', '#000000');
        add_option('cmf_footnoteLinkColor', '#000000');
        add_option('cmf_footnoteLinkHoverUnderlineStyle', 'solid');
        add_option('cmf_footnoteLinkHoverUnderlineWidth', '1');
        add_option('cmf_footnoteLinkHoverUnderlineColor', '#333333');
        add_option('cmf_footnoteLinkHoverColor', '#333333');
        add_option('cmf_footnoteBackground', '#666666');
        add_option('cmf_footnoteForeground', '#ffffff');
        add_option('cmf_footnoteOpacity', 95);
        add_option('cmf_footnoteBorderStyle', 'none');
        add_option('cmf_footnoteBorderWidth', 0);
        add_option('cmf_footnoteBorderColor', '#000000');
        add_option('cmf_footnotePositionTop', 3);
        add_option('cmf_footnotePositionLeft', 23);
        add_option('cmf_footnoteFontSize', 13);
        add_option('cmf_footnotePadding', '2px 12px 3px 7px');
        add_option('cmf_footnoteBorderRadius', 6);

        do_action('cmf_add_options');
    }

    /**
     * Setup plugin constants
     *
     * @access private
     * @since 1.1
     * @return void
     */
    public static function setupConstants()
    {
        /**
         * Define Plugin Directory
         *
         * @since 1.0
         */
        if( !defined('CMF_PLUGIN_DIR') )
        {
            define('CMF_PLUGIN_DIR', plugin_dir_path(__FILE__));
        }

        /**
         * Define Plugin URL
         *
         * @since 1.0
         */
        if( !defined('CMF_PLUGIN_URL') )
        {
            define('CMF_PLUGIN_URL', plugin_dir_url(__FILE__));
        }

        /**
         * Define Plugin Slug name
         *
         * @since 1.0
         */
        if( !defined('CMF_SLUG_NAME') )
        {
            define('CMF_SLUG_NAME', 'cm-footnote-footnote');
        }

        /**
         * Define Plugin basename
         *
         * @since 1.0
         */
        if( !defined('CMF_PLUGIN') )
        {
            define('CMF_PLUGIN', plugin_basename(__FILE__));
        }

        if( !defined('CMF_MENU_OPTION') )
        {
            define('CMF_MENU_OPTION', 'cmf_menu_options');
        }

        define('CMF_ABOUT_OPTION', 'cmf_about');
        define('CMF_PRO_OPTION', 'cmf_pro');
        define('CMF_SETTINGS_OPTION', 'cmf_settings');
        define('CMF_TRANSIENT_ALL_ITEMS_KEY', 'cmf_footnote_index_all_items');

        do_action('cmf_setup_constants_after');
    }

    /**
     * Create custom post type
     */
    public static function cmf_create_post_types()
    {
        $comments = get_option('cmf_footnoteRemoveCommentsTermPage', 1);

        $args = array(
            'label'               => 'Footnote',
            'labels'              => array(
                'add_new_item'  => 'Add New Footnote Item',
                'add_new'       => 'Add Footnote Item',
                'edit_item'     => 'Edit Footnote Item',
                'view_item'     => 'View Footnote Item',
                'singular_name' => 'Footnote Item',
                'name'          => CMF_NAME,
                'menu_name'     => 'Footnote'
            ),
            'description'         => '',
            'map_meta_cap'        => true,
            'publicly_queryable'  => true,
            'exclude_from_search' => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_admin_bar'   => true,
            'show_in_menu'        => CMF_MENU_OPTION,
            '_builtin'            => false,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'has_archive'         => false,
            'rewrite'             => array('slug' => 'footnote', 'with_front' => false, 'feeds' => true, 'feed' => true),
            'query_var'           => true,
            'supports'            => array('title', 'editor', 'author', 'excerpt', 'revisions',
                'custom-fields', 'page-attributes', 'post-thumbnails', 'thumbnail'),
        );

        if( !$comments )
        {
            $args['supports'][] = 'comments';
        }

        register_post_type('footnote', apply_filters('cmf_post_type_args', $args));

        global $wp_rewrite;
        $args = (object) $args;

        $post_type = 'footnote';
        $archive_slug = $args->rewrite['slug'];
        if( $args->rewrite['with_front'] )
        {
            $archive_slug = substr($wp_rewrite->front, 1) . $archive_slug;
        }
        else
        {
            $archive_slug = $wp_rewrite->root . $archive_slug;
        }
        if( $args->rewrite['feeds'] && $wp_rewrite->feeds )
        {
            $feeds = '(' . trim(implode('|', $wp_rewrite->feeds)) . ')';
            add_rewrite_rule("{$archive_slug}/feed/$feeds/?$", "index.php?post_type=$post_type" . '&feed=$matches[1]', 'top');
            add_rewrite_rule("{$archive_slug}/$feeds/?$", "index.php?post_type=$post_type" . '&feed=$matches[1]', 'top');
        }
    }

    public static function cmf_admin_menu()
    {
        global $submenu;
        $current_user = wp_get_current_user();

        add_menu_page('Footnote', CMF_NAME, 'edit_posts', CMF_MENU_OPTION, 'edit.php?post_type=footnote', CMF_PLUGIN_URL . 'assets/css/images/cm-footnote-icon.png');

//        add_submenu_page(CMF_MENU_OPTION, 'Trash', 'Trash', 'edit_posts', 'edit.php?post_status=trash&post_type=footnote');
        add_submenu_page(CMF_MENU_OPTION, 'Add New', 'Add New', 'edit_posts', 'post-new.php?post_type=footnote');
        do_action('cmf_add_admin_menu_after_new');
        add_submenu_page(CMF_MENU_OPTION, 'Footnote Options', 'Settings', 'manage_options', CMF_SETTINGS_OPTION, array(self::$calledClassName, 'outputOptions'));
        add_submenu_page(CMF_MENU_OPTION, 'About', 'About', 'edit_posts', CMF_ABOUT_OPTION, array(self::$calledClassName, 'cmf_about'));
        add_submenu_page(CMF_MENU_OPTION, 'Pro Versions', 'Pro Versions', 'edit_posts', CMF_PRO_OPTION, array(self::$calledClassName, 'cmf_admin_pro'));
        if( user_can($current_user, 'edit_posts') )
        {
            $submenu[CMF_MENU_OPTION][500] = array('User Guide', 'manage_options', 'http://footnote.cminds.com/cm-footnote-user-guide/');
        }

        if( current_user_can('manage_options') )
        {
            $submenu[CMF_MENU_OPTION][999] = array('Yearly membership offer', 'manage_options', self::PAGE_YEARLY_OFFER);
            add_action('admin_head', array(__CLASS__, 'admin_head'));
        }

        $footnoteItemsPerPage = get_user_meta(get_current_user_id(), 'edit_footnote_per_page', true);
        if( $footnoteItemsPerPage && intval($footnoteItemsPerPage) > 100 )
        {
            update_user_meta(get_current_user_id(), 'edit_footnote_per_page', 100);
        }

        add_filter('views_edit-footnote', array(self::$calledClassName, 'cmf_filter_admin_nav'), 10, 1);
    }

    public static function admin_head()
    {
        echo '<style type="text/css">
        		#toplevel_page_' . CMF_MENU_OPTION . ' a[href*="cm-wordpress-plugins-yearly-membership"] {color: white;}
    			a[href*="cm-wordpress-plugins-yearly-membership"]:before {font-size: 16px; vertical-align: middle; padding-right: 5px; color: #d54e21;
    				content: "\f487";
				    display: inline-block;
					-webkit-font-smoothing: antialiased;
					font: normal 16px/1 \'dashicons\';
    			}
    			#toplevel_page_' . CMF_MENU_OPTION . ' a[href*="cm-wordpress-plugins-yearly-membership"]:before {vertical-align: bottom;}

        	</style>';
    }

    public static function cmf_about()
    {
        ob_start();
        require 'views/backend/admin_about.php';
        $content = ob_get_contents();
        ob_end_clean();
        require 'views/backend/admin_template.php';
    }

    /**
     * Shows pro page
     */
    public static function cmf_admin_pro()
    {
        ob_start();
        include_once 'views/backend/admin_pro.php';
        $content = ob_get_contents();
        ob_end_clean();
        include_once 'views/backend/admin_template.php';
    }

    /**
     * Function enqueues the scripts and styles for the admin Settings view
     * @global type $parent_file
     * @return type
     */
    public static function cmf_footnote_admin_settings_scripts()
    {
        global $parent_file;
        if( CMF_MENU_OPTION !== $parent_file )
        {
            return;
        }

        wp_enqueue_style('jqueryUIStylesheet', self::$cssPath . 'jquery-ui-1.10.3.custom.css');
        wp_enqueue_style('footnote', self::$cssPath . 'footnote.css');
        wp_enqueue_script('footnote-admin-js', self::$jsPath . 'cm-footnote.js', array('jquery'));

        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-tooltip');
        wp_enqueue_script('jquery-ui-tabs');

        $footnoteData['ajaxurl'] = admin_url('admin-ajax.php');
        wp_localize_script('footnote-admin-js', 'cmf_data', $footnoteData);
    }

    /**
     * Function outputs the scripts and styles for the edit views
     * @global type $typenow
     * @return type
     */
    public static function cmf_footnote_admin_edit_scripts()
    {
        global $typenow;

        $defaultPostTypes = get_option('cmf_allowed_terms_metabox_all_post_types') ? get_post_types() : array('post', 'page');
        $allowedTermsBoxPostTypes = apply_filters('cmf_allowed_terms_metabox_posttypes', $defaultPostTypes);

        if( !in_array($typenow, $allowedTermsBoxPostTypes) )
        {
            return;
        }

        wp_enqueue_style('footnote', self::$cssPath . 'footnote.css');
        wp_enqueue_script('footnote-admin-js', self::$jsPath . 'cm-footnote.js', array('jquery'));

        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-tooltip');
    }

    /**
     * Filters admin navigation menus to show horizontal link bar
     * @global string $submenu
     * @global type $plugin_page
     * @param type $views
     * @return string
     */
    public static function cmf_filter_admin_nav($views)
    {
        global $submenu, $plugin_page;
        $scheme = is_ssl() ? 'https://' : 'http://';
        $adminUrl = str_replace($scheme . $_SERVER['HTTP_HOST'], '', admin_url());
        $currentUri = str_replace($adminUrl, '', $_SERVER['REQUEST_URI']);
        $submenus = array();
        if( isset($submenu[CMF_MENU_OPTION]) )
        {
            $thisMenu = $submenu[CMF_MENU_OPTION];

            $firstMenuItem = $thisMenu[0];
            unset($thisMenu[0]);

            $secondMenuItem = array('Trash', 'edit_posts', 'edit.php?post_status=trash&post_type=footnote', 'Trash');

            array_unshift($thisMenu, $firstMenuItem, $secondMenuItem);

            foreach($thisMenu as $item)
            {
                $slug = $item[2];
                $isCurrent = ($slug == $plugin_page || strpos($item[2], '.php') === strpos($currentUri, '.php'));
                $isExternalPage = strpos($item[2], 'http') !== FALSE;
                $isNotSubPage = $isExternalPage || strpos($item[2], '.php') !== FALSE;
                $url = $isNotSubPage ? $slug : get_admin_url(null, 'admin.php?page=' . $slug);
                $target = $isExternalPage ? '_blank' : '';
                $submenus[$item[0]] = '<a href="' . $url . '" target="' . $target . '" class="' . ($isCurrent ? 'current' : '') . '">' . $item[0] . '</a>';
            }
        }
        return $submenus;
    }

    public static function cmf_restrict_manage_posts()
    {
        global $typenow;
        if( $typenow == 'footnote' )
        {
            $status = get_query_var('post_status');
            $options = apply_filters('cmf_footnote_restrict_manage_posts', array('published' => 'Published', 'trash' => 'Trash'));

            echo '<select name="post_status">';
            foreach($options as $key => $label)
            {
                echo '<option value="' . $key . '" ' . selected($key, $status) . '>' . CMF_Pro::_e($label) . '</option>';
            }
            echo '</select>';
        }
    }

    /**
     * Displays the horizontal navigation bar
     * @global string $submenu
     * @global type $plugin_page
     */
    public static function cmf_showNav()
    {
        global $submenu, $plugin_page;
        $submenus = array();
        $scheme = is_ssl() ? 'https://' : 'http://';
        $adminUrl = str_replace($scheme . $_SERVER['HTTP_HOST'], '', admin_url());
        $currentUri = str_replace($adminUrl, '', $_SERVER['REQUEST_URI']);

        if( isset($submenu[CMF_MENU_OPTION]) )
        {
            $thisMenu = $submenu[CMF_MENU_OPTION];
            foreach($thisMenu as $item)
            {
                $slug = $item[2];
                $isCurrent = ($slug == $plugin_page || strpos($item[2], '.php') === strpos($currentUri, '.php'));
                $isExternalPage = strpos($item[2], 'http') !== FALSE;
                $isNotSubPage = $isExternalPage || strpos($item[2], '.php') !== FALSE;
                $url = $isNotSubPage ? $slug : get_admin_url(null, 'admin.php?page=' . $slug);
                $submenus[] = array(
                    'link'    => $url,
                    'title'   => $item[0],
                    'current' => $isCurrent,
                    'target'  => $isExternalPage ? '_blank' : ''
                );
            }
            require('views/backend/admin_nav.php');
        }
    }

    /**
     * Returns TRUE if the footnote should be clickable
     */
    public static function isFootnoteClickable($isClickable)
    {
        $isClickableArr['is_clickable'] = (bool) get_option('cmf_footnoteIsClickable');
        $isClickableArr['edit_link'] = (bool) get_option('cmf_footnoteAddTermEditlink') && current_user_can('edit_posts');

        $isClickable = in_array(TRUE, $isClickableArr);
        return $isClickable;
    }

    /**
     * Add the dynamic CSS to reflect the styles set by the options
     * @return type
     */
    public static function cmf_footnote_dynamic_css()
    {
        ob_start();
        echo apply_filters('cmf_dynamic_css_before', '');
        ?>
        span.cmf_has_footnote a.cmf_footnote_link {
        font-size: <?php echo get_option('cmf_footnoteSymbolSize'); ?>;
        }
        .cmf_footnotes_wrapper table.cmf_footnotes_table .cmf_footnote_row .cmf_footnote_link_anchor {
        font-size: <?php echo get_option('cmf_footnoteSymbolLinkAnchorSize'); ?>;
        }
        .cmf_footnotes_wrapper table.cmf_footnotes_table .cmf_footnote_row .cmf_footnote_title {
        font-size: <?php echo get_option('cmf_footnoteTitleSize'); ?>;
        }
        .cmf_footnotes_wrapper table.cmf_footnotes_table .cmf_footnote_row .cmf_footnote_description {
        font-size: <?php echo get_option('cmf_footnoteDescriptionSize'); ?>;
        }
        <?php if( get_option('cmf_footnoteDescriptionBorder') ): ?>
            .cmf_footnotes_wrapper table.cmf_footnotes_table td.cmf_footnote_description{
            border-top: 1px solid #DDD;
            }
        <?php endif; ?>

        <?php
        echo apply_filters('cmf_dynamic_css_after', '');
        $content = ob_get_clean();
        return trim($content);
    }

    /**
     * Outputs the frontend CSS
     */
    public static function cmf_footnote_css()
    {
        $fontName = get_option('cmf_footnoteFontStyle', 'default');

        wp_enqueue_style('footnote', self::$cssPath . 'footnote.css');
        if( is_string($fontName) && $fontName !== 'default' )
        {
            wp_enqueue_style('footnote-google-font', '//fonts.googleapis.com/css?family=' . $fontName);
        }

        /*
         * It's WP 3.3+ function
         */
        if( function_exists('wp_add_inline_style') )
        {
            wp_add_inline_style('footnote', self::cmf_footnote_dynamic_css());
        }
    }

    /**
     * Adds a notice about wp version lower than required 3.3
     * @global type $wp_version
     */
    public static function cmf_footnote_admin_notice_wp33()
    {
        global $wp_version;

        if( version_compare($wp_version, '3.3', '<') )
        {
            $message = sprintf(CMF_Pro::__('%s requires Wordpress version 3.3 or higher to work properly.'), CMF_NAME);
            cminds_show_message($message, true);
        }
    }

    /**
     * Adds a notice about mbstring not being installed
     * @global type $wp_version
     */
    public static function cmf_footnote_admin_notice_mbstring()
    {
        $mb_support = function_exists('mb_strtolower');

        if( !$mb_support )
        {
            $message = sprintf(CMF_Pro::__('%s since version 2.6.0 requires "mbstring" PHP extension to work! '), CMF_NAME);
            $message .= '<a href="http://www.php.net/manual/en/mbstring.installation.php" target="_blank">(' . CMF_Pro::__('Installation instructions.') . ')</a>';
            cminds_show_message($message, true);
        }
    }

    /**
     * Adds a notice about too many footnote items for client pagination
     * @global type $wp_version
     */
    public static function cmf_footnote_admin_notice_client_pagination()
    {
        $serverSide = get_option('cmf_footnoteServerSidePagination');
        $footnoteItemsCount = wp_count_posts('footnote');

        if( !$serverSide && (int) $footnoteItemsCount->publish > 4000 )
        {
            $message = sprintf(CMF_Pro::__('%s has detected that your footnote has more than 4000 terms and the "Client-side" pagination has been selected. <br/>'
                            . 'Please switch to the "Server-side" pagination to avoid slowness and problems with the server memory on the Footnote Index Page.'), CMF_NAME);
            cminds_show_message($message, true);
        }
    }

    /**
     * Strips just one tag
     * @param type $str
     * @param type $tags
     * @param type $stripContent
     * @return type
     */
    public static function cmf_strip_only($str, $tags, $stripContent = false)
    {
        $content = '';
        if( !is_array($tags) )
        {
            $tags = (strpos($str, '>') !== false ? explode('>', str_replace('<', '', $tags)) : array($tags));
            if( end($tags) == '' )
            {
                array_pop($tags);
            }
        }
        foreach($tags as $tag)
        {
            if( $stripContent )
            {
                $content = '(.+</' . $tag . '[^>]*>|)';
            }
            $str = preg_replace('#</?' . $tag . '[^>]*>' . $content . '#is', '', $str);
        }
        return $str;
    }

    /**
     * Disable the parsing for some reason
     * @global type $wp_query
     * @param type $smth
     * @return type
     */
    public static function cmf_disable_parsing($smth)
    {
        global $wp_query;
        if( $wp_query->is_main_query() && !$wp_query->is_singular )
        {  // to prevent conflict with Yost SEO
            remove_filter('the_content', array(self::$calledClassName, 'cmf_footnote_parse'), 9999);
            do_action('cmf_disable_parsing');
        }
        return $smth;
    }

    /**
     * Reenable the parsing for some reason
     * @global type $wp_query
     * @param type $smth
     * @return type
     */
    public static function cmf_reenable_parsing($smth)
    {
        add_filter('the_content', array(self::$calledClassName, 'cmf_footnote_parse'), 9999);
        do_action('cmf_reenable_parsing');
        return $smth;
    }

    /**
     * Function strips the shortcodes if the option is set
     * @param type $content
     * @return type
     */
    public static function cmf_footnote_parse_strip_shortcodes($content, $footnoteItem)
    {
        if( get_option('cmf_footnoteStripShortcode') == 1 )
        {
            $content = strip_shortcodes($content);
        }
        else
        {
            $content = do_shortcode($content);
        }

        return $content;
    }

    /**
     * Function returns TRUE if the given post should be parsed
     * @param type $post
     * @param type $force
     * @return boolean
     */
    public static function cmf_isParsingRequired($post, $force = false, $from_cache = false)
    {
        static $requiredAtLeastOnce = false;
        if( $from_cache )
        {
            /*
             * Could be used to load JS/CSS in footer only when needed
             */
            return $requiredAtLeastOnce;
        }

        /*
         *  Skip parsing for excluded pages and posts (except footnote pages?! - Marcin)
         */
        $parsingDisabled = get_post_meta($post->ID, '_footnote_disable_for_page', true) == 1;
        if( $parsingDisabled )
        {
            return FALSE;
        }

        if( $force )
        {
            return TRUE;
        }

        if( !is_object($post) )
        {
            return FALSE;
        }

        $currentPostType = get_post_type($post);
        $showOnPostTypes = get_option('cmf_footnoteOnPosttypes');
        $showOnHomepageAuthorpageEtc = (!is_page($post) && !is_single($post) && get_option('cmf_footnoteOnlySingle') == 0);
        $onMainQueryOnly = (get_option('cmf_footnoteOnMainQuery') == 1 ) ? is_main_query() : TRUE;

        if( !is_array($showOnPostTypes) )
        {
            $showOnPostTypes = array();
        }
        $showOnSingleCustom = (is_singular($post) && in_array($currentPostType, $showOnPostTypes));

        $isFootnotePage = 'footnote' == $currentPostType;

        if( $isFootnotePage )
        {
            $condition = get_option('cmf_footnoteOnFootnote');
        }
        else
        {
            $condition = ( $showOnHomepageAuthorpageEtc || $showOnSingleCustom );
        }

        $result = $onMainQueryOnly && $condition;
        if( $result )
        {
            $requiredAtLeastOnce = TRUE;
        }
        return $result;
    }

    /**
     * Get's the custom key with the prefix and suffix
     * @param type $key
     * @return type
     */
    public static function getCustomKey($key)
    {
        $customKey = !empty($key) ? '__' . $key . '__' : FALSE;
        return $customKey;
    }

    /**
     * Prepare the data for the parser
     *
     * @global type $footnoteIndexArr
     * @global type $footnoteSearchStringArr
     * @global type $onlySynonyms
     */
    public static function prepareParsingData()
    {
        static $runOnce = FALSE;

        if( $runOnce )
        {
            return;
        }

        global $footnoteIndexArr, $footnoteSearchStringArr, $onlySynonyms;
        /*
         * Initialize $footnoteSearchStringArr as empty array
         */
        $footnoteSearchStringArr = array();
        $onlySynonyms = array();

        $footnote_index = CMF_Pro::getFootnoteItemsSorted();

        //the tag:[footnote_exclude]+[/footnote_exclude] can be used to mark text will not be taken into account by the footnote
        if( $footnote_index )
        {
            $caseSensitive = get_option('cmf_footnoteCaseSensitive', 0);
            $allSynonyms = array();

            /*
             * The loops prepares the search query for the replacement
             */
            foreach($footnote_index as $footnote_item)
            {
                $footnote_title = preg_quote(str_replace('\'', '&#39;', htmlspecialchars(trim($footnote_item->post_title), ENT_QUOTES, 'UTF-8')), '/');

                $addition = '';
                $synonyms = array();
                $synonyms2 = array();
                $onlySynonyms[$footnote_item->ID] = array();

                if( isset($allSynonyms[$footnote_item->ID]) )
                {
                    if( isset($allSynonyms[$footnote_item->ID]['synonym']) )
                    {
                        $synonyms = array_merge($synonyms, $allSynonyms[$footnote_item->ID]['synonym']);
                        $onlySynonyms[$footnote_item->ID] = $allSynonyms[$footnote_item->ID]['synonym'];
                    }
                }

                if( !empty($synonyms) && count($synonyms) > 0 )
                {
                    foreach($synonyms as $val)
                    {
                        $val = preg_quote(str_replace('\'', '&#39;', htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8')), '/');
                        if( !empty($val) )
                        {
                            $synonyms2[] = $val;
                        }
                    }
                    if( !empty($synonyms2) )
                    {
                        $addition = '|' . implode('|', $synonyms2);
                    }
                }

                $additionFiltered = apply_filters('cmf_parse_addition_add', $addition, $footnote_item);

                $footnoteIndexArrKey = $footnote_title . $additionFiltered;
                if( !$caseSensitive )
                {
                    $footnoteIndexArrKey = mb_strtolower($footnoteIndexArrKey);
                }

                $ignore = get_post_meta($footnote_item->ID, '_cmf_not_parsed', true);
                if( !$ignore )
                {
                    $footnoteSearchStringArr[] = $footnote_title . $additionFiltered;
                    $footnoteIndexArr[$footnoteIndexArrKey] = $footnote_item;
                }
                else
                {
                    $customKey = self::getCustomKey(get_post_meta($footnote_item->ID, '_cmf_custom_id', true));
                    if( !empty($customKey) )
                    {
                        global $cmf_specialReplaceRules;

                        if( !empty($cmf_specialReplaceRules) )
                        {
                            foreach($cmf_specialReplaceRules as $customFootnote)
                            {
                                if( $customFootnote['footnoteCustomKey'] !== $customKey )
                                {
                                    continue;
                                }
                                $replaceFrom = $customFootnote['replaceFrom'];

                                $footnoteIndexArrKey = !$caseSensitive ? mb_strtolower($replaceFrom) : $replaceFrom;

                                $footnoteIndexArr[$footnoteIndexArrKey] = $footnote_item;
                                $footnoteSearchStringArr[] = $replaceFrom;
                            }
                        }
                    }
                }
            }
        }

        $runOnce = TRUE;
    }

    public static function cmf_footnote_parse($content, $force = false)
    {
        global $post, $wp_query;

        if( $post === NULL )
        {
            return $content;
        }

        if( !is_object($post) )
        {
            $post = $wp_query->post;
        }

        $seo = doing_action('wpseo_opengraph');
        if( $seo )
        {
            return $content;
        }

        $runParser = self::cmf_isParsingRequired($post, $force);
        if( !$runParser )
        {
            /*
             * Returns empty string
             */
            add_shortcode('cm_footnotes', '__return_empty_string');
            $removeShortcodeContent = do_shortcode($content);
            return $removeShortcodeContent;
        }

        /*
         * Run the footnote parser
         */
        $contentHash = 'cmf_content' . sha1($post->ID);
        if( !$force )
        {
            if( !get_option('cmf_footnoteEnableCaching', TRUE) )
            {
                delete_transient($contentHash);
            }
            $result = get_transient($contentHash);
            if( $result !== false )
            {
                return $result;
            }
        }

        /*
         * Prepare the parsing data
         */
        self::prepareParsingData();

        global $footnoteSearchStringArr, $cmf_replacedTerms;

        if( !empty($footnoteSearchStringArr) && is_array($footnoteSearchStringArr) )
        {
            $caseSensitive = get_option('cmf_footnoteCaseSensitive', 0);

            $excludeFootnote_regex = '/\\['                              // Opening bracket
                    . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
                    . "(footnote_exclude)"                     // 2: Shortcode name
                    . '\\b'                              // Word boundary
                    . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
                    . '[^\\]\\/]*'                   // Not a closing bracket or forward slash
                    . '(?:'
                    . '\\/(?!\\])'               // A forward slash not followed by a closing bracket
                    . '[^\\]\\/]*'               // Not a closing bracket or forward slash
                    . ')*?'
                    . ')'
                    . '(?:'
                    . '(\\/)'                        // 4: Self closing tag ...
                    . '\\]'                          // ... and closing bracket
                    . '|'
                    . '\\]'                          // Closing bracket
                    . '(?:'
                    . '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
                    . '[^\\[]*+'             // Not an opening bracket
                    . '(?:'
                    . '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
                    . '[^\\[]*+'         // Not an opening bracket
                    . ')*+'
                    . ')'
                    . '\\[\\/\\2\\]'             // Closing shortcode tag
                    . ')?'
                    . ')'
                    . '(\\]?)/s';

            $excludeFootnoteStrs = array();

            /*
             * Replace exclude tags and content between them in purpose to save the original text as is
             * before footnote plug go over the content and add its code
             * (later will be returned to the marked places in content)
             */
            $excludeTagsCount = preg_match_all($excludeFootnote_regex, $content, $excludeFootnoteStrs, PREG_PATTERN_ORDER);
            $i = 0;

            if( $excludeTagsCount > 0 )
            {
                foreach($excludeFootnoteStrs[0] as $excludeStr)
                {
                    $content = preg_replace($excludeFootnote_regex, '#' . $i . 'excludeFootnote', $content, 1);
                    $i++;
                }
            }

            $footnoteArrayChunk = apply_filters('cmf_parse_array_chunk_size', 75);
            $spaceSeparated = apply_filters('cmf_parse_space_separated_only', 1);

            if( !is_array($cmf_replacedTerms) )
            {
                $cmf_replacedTerms = array();
            }

            if( count($footnoteSearchStringArr) > $footnoteArrayChunk )
            {
                $chunkedFootnoteSearchStringArr = array_chunk($footnoteSearchStringArr, $footnoteArrayChunk, TRUE);

                foreach($chunkedFootnoteSearchStringArr as $footnoteSearchStringArrChunk)
                {
                    $footnoteSearchString = '/' . (($spaceSeparated) ? '(?<=\P{L}|^)' : '') . '(?!(<|&lt;))(' . (!$caseSensitive ? '(?i)' : '') . implode('|', $footnoteSearchStringArrChunk) . ')(?!(>|&gt;))' . (($spaceSeparated) ? '(?=\P{L}|$)' : '') . '/u';
                    $content = self::cmf_dom_str_replace($content, $footnoteSearchString);
                }
            }
            else
            {
                $footnoteSearchString = '/' . (($spaceSeparated) ? '(?<=\P{L}|^)' : '') . '(?!(<|&lt;))(' . (!$caseSensitive ? '(?i)' : '') . implode('|', $footnoteSearchStringArr) . ')(?!(>|&gt;))' . (($spaceSeparated) ? '(?=\P{L}|$)' : '') . '/u';
                $content = self::cmf_dom_str_replace($content, $footnoteSearchString);
            }

            if( $excludeTagsCount > 0 )
            {
                $i = 0;
                foreach($excludeFootnoteStrs[0] as $excludeStr)
                {
                    $content = str_replace('#' . $i . 'excludeFootnote', $excludeStr, $content);
                    $i++;
                }
                //remove all the exclude signs
                $content = str_replace(array('[footnote_exclude]', '[/footnote_exclude]'), array('', ''), $content);
            }
        }

        $content = apply_filters('cm_footnote_parse_end', $content);

        if( get_option('cmf_footnoteEnableCaching', TRUE) )
        {
            $result = set_transient($contentHash, $content, 1 * MINUTE_IN_SECONDS);
        }

        return $content;
    }

    public static function getFootnotesContent()
    {
        global $cmf_replacedTerms;

        $footnotesContent = '';

        $footnoteDisplayHeaders = get_option('cmf_displayHeadersInFootnote');
        $footnoteDisplayTerm = get_option('cmf_displayTermInFootnote');

        if( !empty($cmf_replacedTerms) )
        {
            $footnotesContent .= '<div class="cmf_footnotes_wrapper">';
            $footnotesContent .= '<table class="cmf_footnotes_table">';

            if( $footnoteDisplayHeaders )
            {
                $footnotesContent .= '<tr class="cmf_footnote_row_headers">';
                $footnotesContent .= '<th>' . CMF_Pro::__(get_option('cmf_footnoteHeaderAnchorTitle', 'Anchor/Title')) . '</th>';
                $footnotesContent .= '<th>' . CMF_Pro::__(get_option('cmf_footnoteHeaderDescription', 'Description')) . '</th>';
                $footnotesContent .= '</tr>';
            }

            foreach($cmf_replacedTerms as $footnoteKey => $footnoteArr)
            {
                $footnoteItem = $footnoteArr['post'];
                $footnoteIndexNumber = $footnoteArr['index'];
                $footnoteIndexSymbol = $footnoteArr['symbol'];
                $footnoteId = 'cmf_footnote_' . $footnoteIndexNumber;

                $footnoteContent = apply_filters('cmf_term_footnote_content', '', $footnoteItem);
                /*
                 * Apply filters for 3rd party widgets additions
                 */
                $footnoteContent = apply_filters('cmf_3rdparty_footnote_content', $footnoteContent, $footnoteItem);
                /*
                 * Add filter to change the footnote item content on the footnote list
                 */
                $footnoteContent = apply_filters('cmf_footnote_content_add', $footnoteContent, $footnoteItem);

                $footnotesContent .= '<tr id="' . $footnoteId . '" class="cmf_footnote_row">';
                $footnotesContent .= '<td><sup class="cmf_footnote_link_anchor">' . $footnoteIndexSymbol . '</sup>';
                if( $footnoteDisplayTerm )
                {
                    $footnotesContent .= '<span class="cmf_footnote_title">' . $footnoteItem->post_title . '</span>';
                }
                $footnotesContent .= '</td>';
                $footnotesContent .= '<td class="cmf_footnote_description">' . $footnoteContent . '</td></tr>';
            }
            $footnotesContent .= '</table>';
            $footnotesContent .= '</div>';
        }

        return $footnotesContent;
    }

    /**
     * Returns TRUE if the shortcode was found
     * @staticvar boolean $found
     * @param type $setFound
     * @return type
     */
    public static function wasShortcodeFound($setFound = FALSE)
    {
        static $found = FALSE;
        if( $setFound )
        {
            $found = $setFound;
        }
        return $found;
    }

    public static function outputFootnotes($content)
    {
        $contentWithFootnotes = do_shortcode($content);

        $shortcodeWasFound = self::wasShortcodeFound();
//        $isWidgetActive = is_active_widget(false, false, 'cmf_displayfootnotes_widget');
        if( !$shortcodeWasFound )
        {
            $footnotesContent = self::getFootnotesContent();
            self::wasShortcodeFound(TRUE);

            $contentWithFootnotes = $contentWithFootnotes . $footnotesContent;
        }

        return $contentWithFootnotes;
    }

    /**
     * [cm_footnote_parse]content[/cm_footnote_parse]
     * @param type $atts
     * @param type $content
     * @return type
     */
    public static function cm_footnote_parse($atts, $content = '')
    {
        global $cmWrapItUp;
        $atts = $atts;

        $cmWrapItUp = true;
        $result = apply_filters('cm_footnote_parse', $content, true);
        $cmWrapItUp = false;
        return $result;
    }

    /**
     * Replaces the matches
     * @global array $cmf_replacedTerms
     * @param type $match
     * @return type
     */
    public static function cmf_replace_matches($match)
    {
        if( !empty($match[0]) )
        {
            $matchedTerm = $match[0];

            global $cmf_specialReplaceRules, $cmf_replacedTerms, $caseSensitive;

            $returnTitle = NULL;
            $verbose = FALSE;
            $setCommonKey = NULL;

            $normalizedKey = preg_quote(str_replace('\'', '&#39;', htmlspecialchars(trim($matchedTerm), ENT_QUOTES, 'UTF-8')), '/');
            $normalizedReplacementKey = (!$caseSensitive) ? mb_strtolower($normalizedKey) : $normalizedKey;

            if( isset($cmf_specialReplaceRules[$normalizedReplacementKey]) )
            {
                $returnTitle = isset($cmf_specialReplaceRules[$normalizedReplacementKey]['replaceTo']) ? $cmf_specialReplaceRules[$normalizedReplacementKey]['replaceTo'] : $returnTitle;
                $verbose = isset($cmf_specialReplaceRules[$normalizedReplacementKey]['verbose']) ? $cmf_specialReplaceRules[$normalizedReplacementKey]['verbose'] : $verbose;
                $setCommonKey = isset($cmf_specialReplaceRules[$normalizedReplacementKey]['footnoteCustomKey']) ? $cmf_specialReplaceRules[$normalizedReplacementKey]['footnoteCustomKey'] : $setCommonKey;
            }

            $replacementText = self::cmf_prepareReplaceTemplate(htmlspecialchars_decode($matchedTerm, ENT_COMPAT), $returnTitle, $verbose, $setCommonKey);
            return $replacementText;
        }
    }

    public static function getFootnoteSymbol($footnoteData)
    {
        /*
         * Symbol = "none"
         */
        $symbol = '';

        $footnoteSymbolOption = get_option('cmf_footnoteSymbolStyle');

        if( $footnoteSymbolOption == 'numbers' )
        {
            $symbol = $footnoteData['index'];
        }
        else if( $footnoteSymbolOption == 'letters' )
        {
            $letters = 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,r,s,t,u,w,x,y,z';
            $lettersArr = explode(',', $letters);
            $lettersCount = count($lettersArr);
            $times = ceil($footnoteData['index'] / $lettersCount);

            $symbolIndex = ($footnoteData['index'] - 1) % $lettersCount;
            $symbol = str_repeat($lettersArr[$symbolIndex], $times);
        }
        else if( $footnoteSymbolOption == 'custom' )
        {
            $symbols = get_option('cmf_footnoteSymbolCustom', '*');
            $symbolsArr = explode(',', $symbols);
            if( !is_array($symbolsArr) || empty($symbols) )
            {
                $symbolsArr = array('*');
            }
            $symbolsCount = count($symbolsArr);
            $times = ceil($footnoteData['index'] / $symbolsCount);

            $symbolIndex = ($footnoteData['index'] - 1) % $symbolsCount;
            $symbol = str_repeat($symbolsArr[$symbolIndex], $times);
        }

        return $symbol;
    }

    /**
     * Function which prepares the templates for the footnote words found in text
     *
     * @param string $title replacement text
     * @return array|string
     */
    public static function cmf_prepareReplaceTemplate($title, $returnTitle = NULL, $verbose = FALSE, $setCommonKey = NULL)
    {
        static $footnoteIndexNumber = 0;

        $returnTitle = (NULL !== $returnTitle) ? $returnTitle : $title;
        $commonKey = (NULL !== $setCommonKey) ? $setCommonKey : $title;

        /*
         * Placeholder for the title
         */
        $titlePlaceholder = '##TITLE_GOES_HERE##';

        /*
         * Array of footnote items, settings
         */
        global $footnoteIndexArr, $onlySynonyms, $caseSensitive, $templatesArr, $removeLinksToTerms, $cmf_replacedTerms, $post;

        /*
         *  Checks whether to show footnotes on this page or not
         */
        $footnotesDisabled = get_post_meta($post->ID, '_footnote_disable_footnote_for_page', true) == 1;

        /*
         *  Checks whether to show links to footnote pages or not
         */
        $linksDisabled = get_post_meta($post->ID, '_footnote_disable_links_for_page', true) == 1;

        /*
         * If TRUE then the links to footnote pages are exchanged with spans
         */
        $removeLinksToTerms = (get_option('cmf_footnoteTermLink') == 1 || $linksDisabled);

        /*
         * If "Highlight first occurance only" option is set
         */
        $highlightFirstOccuranceOnly = (get_option('cmf_footnoteFirstOnly') == 1);

        /*
         * If it's case insensitive, then the term keys are stored as lowercased
         */
        $normalizedTitle = preg_quote(str_replace('\'', '&#39;', htmlspecialchars(trim($title), ENT_QUOTES, 'UTF-8')), '/');
        $titleIndex = (!$caseSensitive) ? mb_strtolower($normalizedTitle) : $normalizedTitle;

        try
        {
            do_action('cmf_replace_template_before_synonyms', $titleIndex, $title);
        }
        catch(FootnoteException $ex)
        {
            /*
             * Trick to stop the execution
             */
            $message = $ex->getMessage();
            return $message;
        }

        /*
         * Upgrade to make it work with synonyms
         */
        if( $footnoteIndexArr )
        {
            /*
             * First - look for exact keys
             */
            if( array_key_exists($titleIndex, $footnoteIndexArr) )
            {
                $footnote_item = $footnoteIndexArr[$titleIndex];
            }
            else
            {
                /*
                 * If not found - try the synonyms
                 */
                foreach($footnoteIndexArr as $key => $value)
                {
                    /*
                     * If we find the term we make sure it's a synonym and not a part of some other term
                     */
                    if( strstr($key, '|') && strstr($key, $titleIndex) )
                    {
                        $synonymsArray = explode('|', $key);
                        if( in_array($titleIndex, $synonymsArray) )
                        {
                            /*
                             * $replace = Footnote Post
                             */
                            $footnote_item = $value;
                            break;
                        }
                    }
                }
            }
        }

        /*
         * Error checking
         */
        if( empty($footnote_item) || !is_object($footnote_item) )
        {
            if( !$verbose && defined('WP_DEBUG') && WP_DEBUG )
            {
                throw new FootnoteException('Error! Post not found for word:' . $titleIndex);
            }
            return $returnTitle;
        }

        $id = $footnote_item->ID;

        /**
         *  If "Highlight first occurance only" option is set, we check if the post has already been highlighted
         */
        if( $highlightFirstOccuranceOnly && is_array($cmf_replacedTerms) && !empty($cmf_replacedTerms) )
        {
            foreach($cmf_replacedTerms as $replacedTerm)
            {
                if( $replacedTerm['postID'] == $id )
                {
                    /*
                     * If the post has already been highlighted
                     */
                    return $returnTitle;
                }
            }
        }

        $excludeFootnote = get_post_meta($id, '_cmf_exclude_footnote', true) || $footnotesDisabled;
        if( $excludeFootnote )
        {
            return $returnTitle;
        }

        /*
         * Save the post item to the global array so it can be used to generate "Related Terms" list
         */
        $cmf_replacedTerms[$commonKey]['post'] = $footnote_item;

        /*
         * Save the post item ID to the global array so it's easy to find out if it has been highlighted in text or not
         */
        $cmf_replacedTerms[$commonKey]['postID'] = $id;

        /*
         * Replacement is already cached - use it
         */
        if( $returnTitle === NULL && !empty($templatesArr[$id]) )
        {
            $templateReplaced = str_replace($titlePlaceholder, $title, $templatesArr[$id]);
            return $templateReplaced;
        }

        if( !isset($cmf_replacedTerms[$commonKey]['index']) )
        {
            /*
             * Index of the footnote
             */
            $cmf_replacedTerms[$commonKey]['index'] = ++$footnoteIndexNumber;
        }

        $currentIndexNumber = $cmf_replacedTerms[$commonKey]['index'];

        $cmf_replacedTerms[$commonKey]['symbol'] = self::getFootnoteSymbol($cmf_replacedTerms[$commonKey]);

        $additionalClass = apply_filters('cmf_term_footnote_additional_class', '', $footnote_item);
        $permalink = apply_filters('cmf_term_footnote_permalink', get_permalink($footnote_item->ID), $footnote_item);

        /*
         * Open in new window
         */
        $windowTarget = (get_option('cmf_footnoteInNewPage') == 1) ? ' target="_blank" ' : '';
        $titleAttr = (get_option('cmf_showTitleAttribute') == 1) ? ' title="Footnote: ' . esc_attr($footnote_item->post_title) . '" ' : '';

        $footnoteId = 'cmf_footnote_' . $currentIndexNumber;

        $footnoteData['index'] = $currentIndexNumber;
        $footnoteSymbol = self::getFootnoteSymbol($footnoteData);

        if( $removeLinksToTerms )
        {
            $linkToFootnote = '';
        }
        else
        {
            $linkToFootnote = '<sup><a href="#' . $footnoteId . '" class="cmf_footnote_link">' . $footnoteSymbol . '</a></sup>';
        }
        $link_replace = '<span  ' . $titleAttr . ' class="cmf_has_footnote ' . $additionalClass . '">' . $titlePlaceholder . $linkToFootnote . '</span>';

        /*
         * Save with $titlePlaceholder - for the synonyms
         */
        $templatesArr[$id] = $link_replace;

        /*
         * Replace it with title to show correctly for the first time
         */
        $link_replace = str_replace($titlePlaceholder, $returnTitle, $link_replace);
        return $link_replace;
    }

    /**
     * Get the base of the Footnote Content on Footnote Index Page
     * @param type $content
     * @param type $footnote_item
     * @return type
     */
    public static function addFootnoteDescriptionFold($content, $footnote_item)
    {
        $foldCharacters = (int) get_option('cmf_footnoteDescriptionCharactersCount');
        $contentLength = strlen($content);

        if( $foldCharacters == '0' || $contentLength < $foldCharacters )
        {
            return $content;
        }

        $shortContent = cminds_truncate($content, $foldCharacters, '', false);

        if( $shortContent < $content )
        {
            $shortWrappedContent = '<div class="cmf_footnote_short">' . $shortContent . '</div>';
            $longWrappedContent = '<div class="cmf_footnote_full">' . $content . '</div>';

            $content = $shortWrappedContent . $longWrappedContent;
        }


        return $content;
    }

    /**
     * Get the base of the Footnote Content on Footnote Index Page
     * @param type $content
     * @param type $footnote_item
     * @return type
     */
    public static function getTheFootnoteContentBase($content, $footnote_item)
    {
        $content = (get_option('cmf_footnoteExcerptHover') && $footnote_item->post_excerpt) ? $footnote_item->post_excerpt : $footnote_item->post_content;
        return $content;
    }

    /**
     * Function adds the editlink
     * @return string
     */
    public static function addEditlinkToFootnote($footnoteItemContent, $footnote_item)
    {
        $showTitle = get_option('cmf_footnoteAddTermEditlink');

        if( $showTitle == 1 && current_user_can('edit_posts') )
        {
            $link = '<a href=&quot;' . get_edit_post_link($footnote_item->ID) . '&quot;>Edit term</a>';
            $footnoteItemEditlink = '<div class=footnoteItemEditlink>' . $link . '</div>';
            /*
             * Add the editlink
             */
            $footnoteItemContent = $footnoteItemEditlink . $footnoteItemContent;
        }

        return $footnoteItemContent;
    }

    /**
     * Add the social share buttons
     * @param string $content
     * @return string
     */
    public static function cmf_footnoteAddShareBox($content = '')
    {
        if( !defined('DOING_AJAX') )
        {
            ob_start();
            require CMF_PLUGIN_DIR . 'views/frontend/social_share.phtml';
            $preContent = ob_get_clean();

            $content = $preContent . $content;
        }

        return $content;
    }

    /**
     * Function responsible for saving the options
     */
    public static function saveOptions()
    {
        $messages = '';
        $_POST = array_map('stripslashes_deep', $_POST);
        $post = $_POST;

        if( isset($post["cmf_footnoteSave"]) || isset($post['cmf_footnoteRelatedRefresh']) )
        {
            do_action('cmf_save_options_berfore', $post, $messages);
            $enqueeFlushRules = false;
            /*
             * Update the page options
             */

            if( apply_filters('cmf_enqueueFlushRules', $enqueeFlushRules, $post) )
            {
                self::_flush_rewrite_rules();
            }

            unset($post['cmf_footnoteID'], $post['cmf_footnoteSave']);

            function cmf_get_the_option_names($k)
            {
                return strpos($k, 'cmf_') === 0;
            }

            $options_names = apply_filters('cmf_thirdparty_option_names', array_filter(array_keys($post), 'cmf_get_the_option_names'));

            foreach($options_names as $option_name)
            {
                if( !isset($post[$option_name]) )
                {
                    update_option($option_name, 0);
                }
                else
                {
                    if( $option_name == 'cmf_index_letters' )
                    {
                        $optionValue = explode(',', $post[$option_name]);
                        $optionValue = array_map('mb_strtolower', $optionValue);
                    }
                    else
                    {
                        $optionValue = is_array($post[$option_name]) ? $post[$option_name] : trim($post[$option_name]);
                    }
                    update_option($option_name, $optionValue);
                }
            }
            do_action('cmf_save_options_after_on_save', $post, array(&$messages));
        }

        do_action('cmf_save_options_after', $post, array(&$messages));

        if( isset($post['cmf_footnoteRelatedRefresh']) )
        {
            CMF_Related::crawlArticles();
            $messages = 'Related Articles Index has been updated';
        }

        if( isset($post['cmf_footnotePluginCleanup']) )
        {
            self::_cleanup();
            $messages = 'CM Footnotes data (terms, options) have been removed from the database.';
        }

        return array('messages' => $messages);
    }

    /**
     * Displays the options screen
     */
    public static function outputOptions()
    {
        $result = self::saveOptions();
        $messages = $result['messages'];

        ob_start();
        require('views/backend/admin_settings.php');
        $content = ob_get_contents();
        ob_end_clean();
        require('views/backend/admin_template.php');
    }

    public static function cmf_quicktags()
    {
        global $post;
        ?>
        <script type="text/javascript">
            if (typeof QTags !== "undefined")
            {
                QTags.addButton('cmf_parse', 'Footnote Parse', '[footnote_parse]', '[/footnote_parse]');
                QTags.addButton('cmf_exclude', 'Footnote Exclude', '[footnote_exclude]', '[/footnote_exclude]');
                QTags.addButton('cmf_translate', 'Footnote Translate', '[footnote_translate term=""]');
                QTags.addButton('cmf_dictionary', 'Footnote Dictionary', '[footnote_dictionary term=""]');
                QTags.addButton('cmf_thesaurus', 'Footnote Thesaurus', '[footnote_thesaurus term=""]');
            }
        </script>
        <?php
    }

    /**
     * Add the prefix before the title on the Footnote Term page
     * @global type $wp_query
     * @param string $title
     * @param type $id
     * @return string
     */
    public static function cmf_footnote_addTitlePrefix($title = '', $id = null)
    {
        global $wp_query;

        if( $id )
        {
            $footnoteItem = get_post($id);
            if( $footnoteItem && 'footnote' == $footnoteItem->post_type && $wp_query->is_single && isset($wp_query->query['post_type']) && $wp_query->query['post_type'] == 'footnote' )
            {
                $prefix = get_option('cmf_footnoteBeforeTitle');
                if( !empty($prefix) )
                {
                    $title = $prefix . $title;
                }
            }
        }

        return $title;
    }

    /**
     * Outputs the Affiliate Referral Snippet
     * @return type
     */
    public static function cmf_getReferralSnippet()
    {
        ob_start();
        ?>
        <span class="footnote_referral_link">
            <a target="_blank" href="https://www.cminds.com/store/footnote/?af=<?php echo get_option('cmf_footnoteAffiliateCode') ?>">
                <img src="https://www.cminds.com/wp-content/uploads/download_footnote.png" width=122 height=22 alt="Download Footnote Pro" title="Download Footnote Pro" />
            </a>
        </span>
        <?php
        $referralSnippet = ob_get_clean();
        return $referralSnippet;
    }

    /**
     * Attaches the hooks adding the custom buttons to TinyMCE and CKeditor
     * @return type
     */
    public static function addRicheditorButtons()
    {
        /*
         *  check user permissions
         */
        if( !current_user_can('edit_posts') && !current_user_can('edit_pages') )
        {
            return;
        }

        // check if WYSIWYG is enabled
        if( 'true' == get_user_option('rich_editing') )
        {
            add_filter('mce_external_plugins', array(self::$calledClassName, 'cmf_mcePlugin'));
            add_filter('mce_buttons', array(self::$calledClassName, 'cmf_mceButtons'));

            add_filter('ckeditor_external_plugins', array(self::$calledClassName, 'cmf_ckeditorPlugin'));
            add_filter('ckeditor_buttons', array(self::$calledClassName, 'cmf_ckeditorButtons'));
        }
    }

    public static function cmf_mcePlugin($plugins)
    {
        $plugins = (array) $plugins;
        $plugins['cmf_footnote'] = self::$jsPath . 'editor/footnote-mce.js';
        return $plugins;
    }

    public static function cmf_mceButtons($buttons)
    {
        array_push($buttons, '|', 'cmf_exclude', 'cmf_parse');
        return $buttons;
    }

    public static function cmf_ckeditorPlugin($plugins)
    {
        $plugins = (array) $plugins;
        $plugins['cmf_footnote'] = self::$jsPath . '/editor/ckeditor/plugin.js';
        return $plugins;
    }

    public static function cmf_ckeditorButtons($buttons)
    {
        array_push($buttons, 'cmf_exclude', 'cmf_parse');
        return $buttons;
    }

    public static function cmf_warn_on_upgrade()
    {
        ?>
        <div style="margin-top: 1em"><span style="color: red; font-size: larger">STOP!</span> Do <em>not</em> click &quot;update automatically&quot; as you will be <em>downgraded</em> to the free version of Footnote. Instead, download the Pro update directly from <a href="http://www.cminds.com/downloads/cm-enhanced-footnote-footnote-premium-version/">http://www.cminds.com/downloads/cm-enhanced-footnote-footnote-premium-version/</a>.</div>
        <div style="font-size: smaller">Footnote Pro does not use WordPress's standard update mechanism. We apologize for the inconvenience!</div>
        <?php
    }

    /**
     * Registers the metaboxes
     */
    public static function cmf_RegisterBoxes()
    {
        add_meta_box('footnote-exclude-box', 'CM Footnotes - Term Properties', array(self::$calledClassName, 'cmf_render_my_meta_box'), 'footnote', 'normal', 'high');

        $defaultPostTypes = get_option('cmf_disable_metabox_all_post_types') ? get_post_types() : array('footnote', 'post', 'page');
        $disableBoxPostTypes = apply_filters('cmf_disable_metabox_posttypes', $defaultPostTypes);
        foreach($disableBoxPostTypes as $postType)
        {
            add_meta_box('footnote-disable-box', 'CM Footnotes - Disables', array(self::$calledClassName, 'cmf_render_disable_for_page'), $postType, 'side', 'high');
            if( $postType !== 'footnote' )
            {
                add_meta_box('footnote-add-box', 'CM Footnotes - Add', array(self::$calledClassName, 'cmf_render_add_for_page'), $postType, 'side', 'high');
            }
        }

        do_action('cmf_register_boxes');
    }

    public static function cmf_render_disable_for_page($post)
    {
        $dTTpage = get_post_meta($post->ID, '_footnote_disable_footnote_for_page', true);
        $disableFootnoteForPage = (int) (!empty($dTTpage) && $dTTpage == 1 );

        $dLpage = get_post_meta($post->ID, '_footnote_disable_links_for_page', true);
        $disableLinkForPage = (int) (!empty($dLpage) && $dLpage == 1 );

        $dpage = get_post_meta($post->ID, '_footnote_disable_for_page', true);
        $disableParsingForPage = (int) (!empty($dpage) && $dpage == 1 );

        echo '<div>';
        echo '<label for="footnote_disable_footnote_for_page" class="blocklabel">';
        echo '<input type="checkbox" name="footnote_disable_footnote_for_page" id="footnote_disable_footnote_for_page" value="1" ' . checked(1, $disableFootnoteForPage, false) . '>';
        echo '&nbsp;&nbsp;&nbsp;Don\'t show the Footnotes on this post/page</label>';
        echo '</div>';

        echo '<div>';
        echo '<label for="footnote_disable_for_page" class="blocklabel">';
        echo '<input type="checkbox" name="footnote_disable_for_page" id="footnote_disable_for_page" value="1" ' . checked(1, $disableParsingForPage, false) . '>';
        echo '&nbsp;&nbsp;&nbsp;Don\'t search for footnote items on this post/page</label>';
        echo '</div>';

        do_action('cmf_add_disables_metabox', $post);
    }

    public static function cmf_render_add_for_page($post)
    {
        add_thickbox();
        echo '<a href="#TB_inline?width=753&height=650&inlineId=add_new_footnote" class="thickbox button-primary">' . CMF_Pro::__('Add new Footnote') . '</a>';
    }

    public static function addFootnoteThickbox()
    {
        global $pagenow, $post;
        if( !in_array($pagenow, array('post.php', 'post-new.php')) )
        {
            return;
        }

        if( empty($post) )
        {
            return;
        }

        $defaultPostTypes = get_option('cmf_disable_metabox_all_post_types') ? get_post_types() : array('post', 'page');
        $disableBoxPostTypes = apply_filters('cmf_disable_metabox_posttypes', $defaultPostTypes);

        if( $post->post_type === 'footnote' || !in_array($post->post_type, $disableBoxPostTypes) )
        {
            return;
        }

        /*
         * once we get here we are on the right page so we echo form html:
         */
        ?>
        <div id="add_new_footnote" style="display:none">
            <h2>Add new Footnote</h2>
            <div id="cmf_footnote_ajax_message_container">
                <div class="no-change" style="display: none">The Footnote already exists.</div>
                <div class="update" style="display: none">The Footnote has been updated.</div>
                <div class="insert" style="display: none">New Footnote has been added!</div>
            </div>
            <form method="POST" action="" id="add-new-footnote-form">
                <h3>Title</h3>
                <input type="text" name="footnote_post_title" id="cmf_footnote_post_title" value="" placeholder="Footnote term">
                <h3>Description</h3>
                <?php
                wp_editor('', 'footnotepostcontent', array('teeny' => true, 'textarea_rows' => 5, 'media_buttons' => false, 'tinymce' => false));
                ?>
                <h3>Restrict to this page</h3>
                <label>
                    Show this Footnote only on this page.
                    <input type="hidden" name="page_id" id="cmf_page_id" value="<?php echo $post->ID; ?>" placeholder="">
                    <input type="checkbox" name="restrict_to_page" id="cmf_restrict_to_page" value="1" />
                </label>
                <h3>Update</h3>
                <label>
                    Update if Footnote with given title already exists?
                    <input type="checkbox" name="update" id="cmf_update" value="1" />
                </label>
                <p>
                    <input type="submit" name="" class="button-primary" value="Add Footnote">
                    <input type="reset" name="" class="button-secondary" value="Clear Form">
                </p>
            </form>
        </div>

        <style>
            #cmf_footnote_ajax_message_container{
                font-size: 17px;
                text-align: center;
                font-weight: bold;
            }
            #cmf_footnote_ajax_message_container > div{
                border: 1px solid #ccc;
                border-radius: 5px;
                padding: 5px;
                background: #F5F5F5;
            }

        </style>

        <script>
            (function ($) {
                $('#add-new-footnote-form').submit(function (e) {
                    $.ajax({
                        data: {
                            'action': 'cmf_add_new_footnote',
                            'post_title': $(this).find('#cmf_footnote_post_title').val(),
                            'post_content': $(this).find('#footnotepostcontent').val(),
                            'page_id': $(this).find('#cmf_page_id').val(),
                            'restrict_to_page': $(this).find('#cmf_restrict_to_page').prop('checked') ? 1 : 0,
                            'update': $(this).find('#cmf_update').prop('checked') ? 1 : 0
                        },
                        type: 'post',
                        url: window.ajaxurl,
                        success: function (data) {
                            //cmf_footnote_ajax_clear_form();
                            cmf_footnote_ajax_show_msg(data.msg);
                        }
                    });

                    return false;
                });

                var cmf_footnote_ajax_clear_form = function ()
                {
                    $('#add-new-footnote-form').each(function () {
                        this.reset();
                    });
                };

                var cmf_footnote_ajax_show_msg = function (msg)
                {
                    $('#cmf_footnote_ajax_message_container').children().each(function () {
                        $(this).hide();
                    });
                    $('#cmf_footnote_ajax_message_container').find('.' + msg).show('fast');
                };

            }(jQuery));
        </script>
        <?php
    }

    public static function ajaxAddNewFootnote()
    {
        $return = array();
        $post = filter_input_array(INPUT_POST);

        $postArr = array(
            'post_title'   => $post['post_title'],
            'post_content' => $post['post_content'],
            'post_type'    => 'footnote',
            'post_status'  => 'publish',
        );

        $update = $post['update'];

        global $wpdb;
        $footnoteExists = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type='%s' AND post_title = '%s'", 'footnote', $post['post_title']));

        if( $footnoteExists )
        {
            $footnote = reset($footnoteExists);
            $footnoteId = $footnote->ID;
            $postArr['ID'] = $footnoteId;

            if( $update )
            {
                wp_update_post($postArr);
                $return['msg'] = 'update';
            }
            else
            {
                $return['msg'] = 'no-change';
            }
        }
        else
        {
            $footnoteId = wp_insert_post($postArr);
            $footnote = get_post($footnoteId);
            $return['msg'] = 'insert';
        }

        if( $footnoteId && $post['restrict_to_page'] )
        {
            $currentPageId = $post['page_id'];
            $footnoteDisplayMode = get_post_meta($footnoteId, '_cmf_footnote_display_mode', true);
            $footnotePostExceptionsList = get_post_meta($footnoteId, '_cmf_footnote_post_exceptions');

            if( is_array($footnotePostExceptionsList) && !in_array($currentPageId, $footnotePostExceptionsList) )
            {
                update_post_meta($footnoteId, '_cmf_footnote_post_exceptions', $currentPageId);
            }

            update_post_meta($footnoteId, '_cmf_footnote_display_mode', self::DISPLAY_ONLY_ON_PAGES, $footnoteDisplayMode);
        }

        wp_send_json($return);
        exit();
    }

    public static function footnoteProperies($properties)
    {
        $properties['cmf_custom_id'] = array('label' => CMF_Pro::__('Select Custom ID. Required to output Footnote with the shortcode.'), 'html_atts' => 'size="10"');
        $properties['cmf_not_parsed'] = CMF_Pro::__('Do not highlight automatically. Only with shortcode [cm_custom_footnote id="&lt;Custom ID set above&gt;"]');
        $properties['cmf_footnote_display_mode'] = array('label'   => CMF_Pro::__('Select display method'),
            'default' => self::DISPLAY_EVERYWHERE,
            'options' => array(
                self::DISPLAY_EVERYWHERE      => CMF_Pro::__('Displayed on all pages'),
                self::DISPLAY_EXCEPT_ON_PAGES => CMF_Pro::__('Not displayed on selected pages'),
                self::DISPLAY_ONLY_ON_PAGES   => CMF_Pro::__('Displayed only on selected pages'),
                self::DISPLAY_NOWHERE         => CMF_Pro::__('Not displayed'),
        ));
        $properties['cmf_footnote_post_exceptions'] = array('_label' => CMF_Pro::__('Selected pages'), 'label' => FALSE, 'callback' => array(__CLASS__, 'renderPageSelector'));

        return $properties;
    }

    public static function renderPageSelector($key, $atts, $post)
    {
        $innterContent = '';

        $fieldValue = get_post_meta($post->ID, '_' . $key, true);

        if( !empty($fieldValue) )
        {
            if( !is_array($fieldValue) )
            {
                $fieldValue = array($fieldValue);
            }

            foreach($fieldValue as $fieldIndex => $fieldPageId)
            {
                $dropdownArgs = array(
                    'echo'     => 0,
                    'name'     => $key . '[]',
                    'selected' => $fieldPageId,
                    'id'       => $key . '_' . $fieldIndex
                );
                $innterContent .= '<div class="selectedPageRow">' . cmf_cminds_dropdown($dropdownArgs) . '<a class="remove button-secondary">' . self::__('Remove') . '</a></div>';
            }
        }

        $dropdownArgs = array(
            'echo'             => 0,
            'name'             => $key . '[]',
            'show_option_none' => self::__('-None-'),
            'id'               => ''
        );
        $innterContent .= '<div class="selectedPageRow toAdd" style="display:none">' . cmf_cminds_dropdown($dropdownArgs) . '<a class="remove button-secondary">' . self::__('Remove') . '</a></div>';
        $innterContent .= '<p><a class="add button-primary">' . self::__('Add') . '</a></p>';
        ob_start();
        ?>
        <script>
            (function ($) {

                $(document).ready(function () {
                    $('div.cmfPageSelector').on('click', 'a.remove', function () {
                        var rowToRemove = $(this).parents('div.selectedPageRow');
                        rowToRemove.remove();
                        return false;
                    });

                    $('div.cmfPageSelector a.add').on('click', function () {
                        var rowToAdd = $(this).parents('div.cmfPageSelector').find('div.selectedPageRow.toAdd');
                        var clone = rowToAdd.clone().removeClass('toAdd').show();

                        rowToAdd.before(clone);
                        return false;
                    });

                    $('select#cmf_footnote_display_mode').on('change', function () {
                        var val = $(this).val();
                        var pageSelector = $('.cmfPageSelector');

                        if (val === '0' || val === '1')
                        {
                            pageSelector.hide();
                        }
                        else
                        {
                            pageSelector.show();
                        }
                    }).trigger('change');
                });

            }(jQuery));
        </script>
        <?php
        $scriptContent = ob_get_clean();
        $content = '<div class="cmfPageSelector"><h4>Selected pages</h4>' . $innterContent . $scriptContent . '</div>';

        return $content;
    }

    public static function cmf_footnote_meta_box_fields()
    {
        $metaBoxFields = apply_filters('cmf_add_properties_metabox', array());
        return $metaBoxFields;
    }

    public static function cmf_render_my_meta_box($post)
    {
        $result = array();

        foreach(self::cmf_footnote_meta_box_fields() as $key => $fieldValueArr)
        {
            $optionContent = '<p><label for="' . $key . '" class="blocklabel">';
            $fieldValue = get_post_meta($post->ID, '_' . $key, true);

            if( $fieldValue === '' && !empty($fieldValueArr['default']) )
            {
                $fieldValue = $fieldValueArr['default'];
            }

            if( is_string($fieldValueArr) )
            {
                $label = $fieldValueArr;
                $optionContent .= '<input type="checkbox" name="' . $key . '" id="' . $key . '" value="1" ' . checked('1', $fieldValue, false) . '>';
            }
            elseif( is_array($fieldValueArr) )
            {
                $label = isset($fieldValueArr['label']) ? $fieldValueArr['label'] : CMF_Pro::__('No label');

                if( array_key_exists('options', $fieldValueArr) )
                {
                    $options = isset($fieldValueArr['options']) ? $fieldValueArr['options'] : array('' => CMF_Pro::__('-no options-'));
                    $optionContent .= '<select name="' . $key . '" id="' . $key . '">';
                    foreach($options as $optionKey => $optionLabel)
                    {
                        $optionContent .= '<option value="' . $optionKey . '" ' . selected($optionKey, $fieldValue, false) . '>' . $optionLabel . '</option>';
                    }
                    $optionContent .= '</select>';
                }
                else if( array_key_exists('callback', $fieldValueArr) )
                {
                    $optionContent .= call_user_func($fieldValueArr['callback'], $key, $fieldValueArr, $post);
                }
                else
                {
                    $type = isset($fieldValueArr['type']) ? $fieldValueArr['type'] : 'text';
                    $htmlAtts = isset($fieldValueArr['html_atts']) ? $fieldValueArr['html_atts'] : '';
                    $optionContent .= '<input type="' . $type . '" name="' . $key . '" id="' . $key . '" value="' . $fieldValue . '" ' . $htmlAtts . '>';
                }
            }

            if( !empty($label) )
            {
                $optionContent .= '&nbsp;&nbsp;&nbsp;' . $label . '</label>';
            }

            $optionContent .= '</p>';

            $result[] = $optionContent;
        }

        $result = apply_filters('cmf_edit_properties_metabox_array', $result);

        echo implode('', $result);
    }

    public static function cmf_save_postdata($post_id)
    {
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $postType = isset($post['post_type']) ? $post['post_type'] : '';

        do_action('cmf_on_footnote_item_save_before', $post_id, $post);

        $disableBoxPostTypes = apply_filters('cmf_disable_metabox_posttypes', array('footnote', 'post', 'page'));
        if( in_array($postType, $disableBoxPostTypes) )
        {
            /*
             * Disables the parsing of the given page
             */
            $disableParsingForPage = 0;
            if( isset($post["footnote_disable_for_page"]) && $post["footnote_disable_for_page"] == 1 )
            {
                $disableParsingForPage = 1;
            }
            update_post_meta($post_id, '_footnote_disable_for_page', $disableParsingForPage);

            /*
             * Disables the showing of footnote on given page
             */
            $disableFootnoteForPage = 0;
            if( isset($post["footnote_disable_footnote_for_page"]) && $post["footnote_disable_footnote_for_page"] == 1 )
            {
                $disableFootnoteForPage = 1;
            }
            update_post_meta($post_id, '_footnote_disable_footnote_for_page', $disableFootnoteForPage);
        }

        if( 'footnote' != $postType )
        {
            return;
        }

        do_action('cmf_on_footnote_item_save', $post_id, $post);

        /*
         * Invalidate the list of all footnote items stored in cache
         */
        delete_transient(CMF_TRANSIENT_ALL_ITEMS_KEY);

        /*
         * Part for "footnote" items only starts here
         */
        foreach(array_keys(self::cmf_footnote_meta_box_fields()) as $value)
        {
            $metaValue = (isset($post[$value])) ? $post[$value] : 0;
            if( is_array($metaValue) )
            {
                delete_post_meta($post_id, '_' . $value);
                $metaValue = array_filter($metaValue);
            }
            update_post_meta($post_id, '_' . $value, $metaValue);
        }
    }

    /**
     * Function for adding metabox
     *
     * @param type $args
     * @return type
     */
    public static function addFootnote($args)
    {
        $postExists = true;

        $data = wp_parse_args($args, array(
            'post_type' => 'footnote'
        ));

        if( $postExists )
        {
            $result = wp_update_post($data);
        }
        else
        {
            $result = wp_insert_post($data);
        }

        /*
         * Add option to limit to current page/post
         */

        return $result;
    }

    /**
     * New function to search the terms in the content
     *
     * @param strin $html
     * @param string $footnoteSearchString
     * @since 2.3.1
     * @return type
     */
    public static function cmf_dom_str_replace($html, $footnoteSearchString)
    {
        global $cmWrapItUp;

        if( !empty($html) && is_string($html) )
        {
            if( $cmWrapItUp )
            {
                $html = '<span>' . $html . '</span>';
            }
            $dom = new DOMDocument();
            /*
             * loadXml needs properly formatted documents, so it's better to use loadHtml, but it needs a hack to properly handle UTF-8 encoding
             */
            libxml_use_internal_errors(true);
            if( !$dom->loadHtml(mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8")) )
            {
                libxml_clear_errors();
            }
            $xpath = new DOMXPath($dom);

            /*
             * Base query NEVER parse in scripts
             */
            $query = '//text()[not(ancestor::script)]';
            if( get_option('cmf_footnoteProtectedTags') == 1 )
            {
                $query .= '[not(ancestor::header)][not(ancestor::a)][not(ancestor::pre)][not(ancestor::object)][not(ancestor::h1)][not(ancestor::h2)][not(ancestor::h3)][not(ancestor::h4)][not(ancestor::h5)][not(ancestor::h6)]';
            }

            foreach($xpath->query($query) as $node)
            {
                /* @var $node DOMText */
                $replaced = preg_replace_callback($footnoteSearchString, array(self::$calledClassName, 'cmf_replace_matches'), htmlspecialchars($node->wholeText, ENT_COMPAT));
                if( !empty($replaced) )
                {
                    $newNode = $dom->createDocumentFragment();
                    $replacedShortcodes = strip_shortcodes($replaced);
                    $result = $newNode->appendXML('<![CDATA[' . $replacedShortcodes . ']]>');

                    if( $result !== false )
                    {
                        $node->parentNode->replaceChild($newNode, $node);
                    }
                }
            }

            /*
             *  get only the body tag with its contents, then trim the body tag itself to get only the original content
             */
            $bodyNode = $xpath->query('//body')->item(0);

            if( $bodyNode !== NULL )
            {
                $newDom = new DOMDocument();
                $newDom->appendChild($newDom->importNode($bodyNode, TRUE));

                $intermalHtml = $newDom->saveHTML();
                $html = mb_substr(trim($intermalHtml), 6, (mb_strlen($intermalHtml) - 14), "UTF-8");
                /*
                 * Fixing the self-closing which is lost due to a bug in DOMDocument->saveHtml() (caused a conflict with NextGen)
                 */
                $html = preg_replace('#(<img[^>]*[^/])>#Ui', '$1/>', $html);
            }
        }

        if( $cmWrapItUp )
        {
            $html = mb_substr(trim($html), 6, (mb_strlen($html) - 13), "UTF-8");
        }

        return $html;
    }

    /**
     * BuddyPress record custom post type comments
     * @param array $post_types
     * @return string
     */
    public static function cmf_bp_record_my_custom_post_type_comments($post_types)
    {
        $post_types[] = 'footnote';
        return $post_types;
    }

    public static function getFootnoteByCustomId($id)
    {
        static $resultsCache = array();

        if( !isset($resultsCache[$id]) )
        {
            $args = array(
                'post_type'              => 'footnote',
                'post_status'            => 'publish',
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'suppress_filters'       => false,
                'numberposts'            => 1,
            );

            $metaQueryArgs = array(
                array(
                    'key'   => '_cmf_custom_id',
                    'value' => $id,
                )
            );
            $args['meta_query'] = $metaQueryArgs;

            $query = new WP_Query($args);

            $posts = $query->get_posts();
            $result = !empty($posts);

            $resultsCache[$id] = $result;
        }
        else
        {
            $result = $resultsCache[$id];
        }

        return $result;
    }

    /**
     * Function renders (default) or returns the setttings tabs
     *
     * @param type $return
     * @return string
     */
    public static function renderSettingsTabs($return = false)
    {
        $content = '';
        $settingsTabsArrayBase = array();

        $settingsTabsArray = apply_filters('cmf-settings-tabs-array', $settingsTabsArrayBase);

        if( $settingsTabsArray )
        {
            foreach($settingsTabsArray as $tabKey => $tabLabel)
            {
                $filterName = 'cmf-custom-settings-tab-content-' . $tabKey;

                $content .= '<div id="tabs-' . $tabKey . '">';
                $tabContent = apply_filters($filterName, '');
                $content .= $tabContent;
                $content .= '</div>';
            }
        }

        if( $return )
        {
            return $content;
        }
        echo $content;
    }

    /**
     * Function renders (default) or returns the setttings tabs
     *
     * @param type $return
     * @return string
     */
    public static function renderSettingsTabsControls($return = false)
    {
        $content = '';
        $settingsTabsArrayBase = array(
            '1'  => 'General Settings',
            '3'  => 'Footnote',
            '99' => 'Server Information',
        );

        $settingsTabsArray = apply_filters('cmf-settings-tabs-array', $settingsTabsArrayBase);

        ksort($settingsTabsArray);

        if( $settingsTabsArray )
        {
            $content .= '<ul>';
            foreach($settingsTabsArray as $tabKey => $tabLabel)
            {
                $content .= '<li><a href="#tabs-' . $tabKey . '">' . $tabLabel . '</a></li>';
            }
            $content .= '</ul>';
        }

        if( $return )
        {
            return $content;
        }
        echo $content;
    }

    /**
     * Returns the list of sorted footnote items
     * @staticvar array $footnote_index_full_sorted
     * @param type $args
     * @return type
     */
    public static function getFootnoteItemsSorted()
    {
        static $footnote_index_full_sorted = array();

        if( $footnote_index_full_sorted === array() )
        {
            $footnote_index = self::getFootnoteItems();
            $footnote_index_full_sorted = $footnote_index;
            uasort($footnote_index_full_sorted, array(self::$calledClassName, '_sortByWPQueryObjectTitleLength'));
        }

        return $footnote_index_full_sorted;
    }

    /**
     * Returns the cachable array of all Footnote Terms, either sorted by title, or by title length
     *
     * @staticvar array $footnote_index
     * @staticvar array $footnote_index_sorted
     * @param type $args
     * @return type
     */
    public static function getFootnoteItems($args = array())
    {
        static $footnote_index_cache = array();

        $footnoteItems = array();
        $footnote_index = array();

        $argsKey = 'cmf_' . md5('args' . json_encode($args));

        if( !isset($footnote_index_cache[$argsKey]) )
        {
            if( !get_option('cmf_footnoteEnableCaching', TRUE) )
            {
                delete_transient($argsKey);
            }
            if( false === ($footnoteItems = get_transient($argsKey) ) )
            {
                $defaultArgs = array(
                    'post_type'              => 'footnote',
                    'post_status'            => 'publish',
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                    'suppress_filters'       => false,
                );

                $queryArgs = array_merge($defaultArgs, $args);

                $nopaging_args = $queryArgs;
                $nopaging_args['nopaging'] = true;
                $nopaging_args['numberposts'] = -1;

                if( $args === array() )
                {
                    $queryArgs = $nopaging_args;
                }

                $query = new WP_Query;
                $footnoteIndex = $query->query($queryArgs);

                foreach($footnoteIndex as $post)
                {
                    $obj = new stdClass();
                    $obj->ID = $post->ID;
                    $obj->post_title = $post->post_title;
                    $obj->post_content = $post->post_content;
                    $obj->post_excerpt = $post->post_excerpt;
                    $obj->post_date = $post->post_date;

                    $newObj = apply_filters('cmf_get_all_footnote_items_single', $obj, $post);
                    $footnote_index[] = $newObj;
                }

                $footnoteItems['index'] = $footnote_index;
                $footnoteItems['query'] = $query;
                $footnoteItems['args'] = $queryArgs;
                $footnoteItems['nopaging_args'] = $nopaging_args;

                if( get_option('cmf_footnoteEnableCaching', TRUE) )
                {
                    set_transient($argsKey, $footnoteItems, 1 * MINUTE_IN_SECONDS);
                }
            }

            $footnote_index = $footnoteItems['index'];
            /*
             * Save statically
             */
            self::$lastQueryDetails = $footnoteItems;
        }

        return $footnote_index;
    }

    public static function outputCustomPostTypesList()
    {
        $content = '';
        $args = array(
            'public' => true,
//            '_builtin' => false
        );

        $output = 'objects'; // names or objects, note names is the default
        $operator = 'and'; // 'and' or 'or'

        $post_types = get_post_types($args, $output, $operator);
        $selected_post_types = get_option('cmf_footnoteOnPosttypes');

        if( !is_array($selected_post_types) )
        {
            $selected_post_types = array();
        }

        foreach($post_types as $post_type)
        {
//            var_dump($post_type);
            $label = $post_type->labels->singular_name . ' (' . $post_type->name . ')';
            $name = $post_type->name;

            if( in_array($name, array('post', 'page')) )
            {
                $content .= '<div><label><input type="checkbox" name="cmf_footnoteOnPosttypes[]" ' . checked(true, in_array($name, $selected_post_types), false) . ' value="' . $name . '" />' . $label . '</label></div>';
            }
        }
        return $content;
    }

    /*
     *  Sort longer titles first, so if there is collision between terms
     * (e.g., "essential fatty acid" and "fatty acid") the longer one gets created first.
     */

    public static function _sortByWPQueryObjectTitleLength($a, $b)
    {
        $sortVal = 0;
        if( property_exists($a, 'post_title') && property_exists($b, 'post_title') )
        {
            $sortVal = strlen($b->post_title) - strlen($a->post_title);
        }
        return $sortVal;
    }

    /**
     * Function cleans up the plugin, removing the terms, resetting the options etc.
     *
     * @return string
     */
    protected static function _cleanup($force = true)
    {
        $footnote_index = self::getFootnoteItems();

        /*
         * Remove the footnote terms
         */
        foreach($footnote_index as $post)
        {
            wp_delete_post($post->ID, $force);
        }

        /*
         * Invalidate the list of all footnote items stored in cache
         */
        delete_transient(CMF_TRANSIENT_ALL_ITEMS_KEY);

        /*
         * Remove the data from the other tables
         */
        do_action('cmf_do_cleanup');

        /*
         * Remove the options
         */
        $optionNames = wp_load_alloptions();

        function cmf_get_the_option_names($k)
        {
            return strpos($k, 'cmf_') === 0;
        }

        $options_names = array_filter(array_keys($optionNames), 'cmf_get_the_option_names');
        foreach($options_names as $optionName)
        {
            delete_option($optionName);
        }
    }

    /**
     * Plugin activation
     */
    protected static function _activate()
    {
        do_action('cmf_do_activate');
    }

    /**
     * Plugin installation
     *
     * @global type $wpdb
     * @param type $networkwide
     * @return type
     */
    public static function _install($networkwide)
    {
        global $wpdb;

        if( function_exists('is_multisite') && is_multisite() )
        {
            // check if it is a network activation - if so, run the activation function for each blog id
            if( $networkwide )
            {
                $old_blog = $wpdb->blogid;
                // Get all blog ids
                $blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM {$wpdb->blogs}"));
                foreach($blogids as $blog_id)
                {
                    switch_to_blog($blog_id);
                    self::_activate();
                }
                switch_to_blog($old_blog);
                return;
            }
        }

        self::_activate();
    }

    /**
     * Flushes the rewrite rules to reflect the permalink changes automatically (if any)
     *
     * @global type $wp_rewrite
     */
    public static function _flush_rewrite_rules()
    {
        global $wp_rewrite;
        // First, we "add" the custom post type via the above written function.

        self::cmf_create_post_types();

        do_action('cmf_flush_rewrite_rules');

        // Clear the permalinks
        flush_rewrite_rules();

        //Call flush_rules() as a method of the $wp_rewrite object
        $wp_rewrite->flush_rules();
    }

    /**
     * Scoped i18n function
     * @param type $message
     * @return type
     */
    public static function __($message)
    {
        return __($message, CMF_SLUG_NAME);
    }

    /**
     * Scoped i18n function
     * @param type $message
     * @return type
     */
    public static function _e($message)
    {
        return _e($message, CMF_SLUG_NAME);
    }

}