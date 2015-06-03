<?php

class CMF_Related
{
    const TABLENAME = 'footnote_related';

    public static $tableExists = false;

    public static function init()
    {
        add_action('save_post', array(__CLASS__, 'triggerOnSave'), 1000);
        add_action('cmf_do_cleanup', array(__CLASS__, 'doCleanup'));
        add_action('cmf_do_activate', array(__CLASS__, 'install'));
        add_filter('cron_schedules', array(get_class(), 'cronAddIntervals'));
        add_action('admin_init', array(get_class(), 'reschedule'));
    }

    public static function install()
    {
        global $wpdb;
        $sql = "CREATE TABLE {$wpdb->prefix}" . self::TABLENAME . " (
            footnoteId INTEGER UNSIGNED NOT NULL,
            articleId VARCHAR(145) NOT NULL,
            PRIMARY KEY  (articleId,footnoteId),
            KEY footnoteId (footnoteId)
          );";

        include_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        wp_schedule_event(current_time('timestamp'), 'daily', 'footnote_daily_event');
    }

    public static function checkIfTableExists()
    {
        global $wpdb;

        if( !empty(self::$tableExists) )
        {
            return self::$tableExists;
        }

        if( !$wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}" . self::TABLENAME . "'") == $wpdb->prefix . self::TABLENAME )
        {
            self::install();
        }
        self::$tableExists = true;
        return self::$tableExists;
    }

    public static function doCleanup()
    {
        self::flushDb();
    }

    public static function flushDb()
    {
        global $wpdb;
        $wpdb->query('DELETE FROM ' . $wpdb->prefix . self::TABLENAME);
    }

    public static function cronAddIntervals($schedules)
    {
        // add a 'weekly' interval
        $schedules['weekly'] = array(
            'interval' => 604800,
            'display'  => CMF_Pro::__('Once Weekly')
        );
        $schedules['monthly'] = array(
            'interval' => 2635200,
            'display'  => CMF_Pro::__('Once Monthly')
        );
        return $schedules;
    }

    public static function reschedule()
    {
        $possibleIntervals = array_keys(wp_get_schedules());

        $newScheduleHour = filter_input(INPUT_POST, 'cmf_footnote_relatedCronHour');
        $newScheduleInterval = filter_input(INPUT_POST, 'cmf_footnote_relatedCronInterval');

        if( $newScheduleHour !== NULL && $newScheduleInterval !== NULL )
        {
            wp_clear_scheduled_hook('footnote_daily_event');

            if( $newScheduleInterval == 'none' )
            {
                return;
            }

            if( !in_array($newScheduleInterval, $possibleIntervals) )
            {
                $newScheduleInterval = 'daily';
            }

            $time = strtotime($newScheduleHour);
            if( $time === FALSE )
            {
                $time = current_time('timestamp');
            }

            wp_schedule_event($time, $newScheduleInterval, 'footnote_daily_event');
        }
    }

    public static function updateArticleTerms($id, $content)
    {
        global $templatesArr, $wpdb;

        $templatesArr = array();
        CMF_Pro::cmf_footnote_parse($content, true);

        $wpdb->query("DELETE FROM " . $wpdb->prefix . self::TABLENAME . " WHERE articleId=" . $id);

        if( !empty($templatesArr) )
        {
            $footnoteIds = array_keys($templatesArr);
            foreach($footnoteIds as $footnoteId)
            {
                if( $footnoteId != $id )
                {
                    $wpdb->insert($wpdb->prefix . self::TABLENAME, array('articleId' => $id, 'footnoteId' => $footnoteId), array('%d', '%d'));
                }
            }
        }
    }

    public static function crawlArticles($types = array('post', 'page'))
    {
        global $wpdb, $post;
        set_time_limit(0);
        $types = get_option('cmf_footnote_showRelatedArticlesPostTypesArr');

        self::checkIfTableExists();

        $wpdb->query("DELETE FROM " . $wpdb->prefix . self::TABLENAME);
        $args = array(
            'post_type'   => $types,
            'post_status' => 'publish',
            'nopaging'    => true
        );
        $q = new WP_Query($args);
        foreach($q->get_posts() as $article)
        {
            $post = $article;
            self::updateArticleTerms($article->ID, $article->post_content);
        }
    }

    public static function triggerOnSave($post_id)
    {
        self::checkIfTableExists();
        if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        $post = get_post($post_id);
        $postTypes = get_option('cmf_footnote_showRelatedArticlesPostTypesArr');
        if( (is_array($postTypes) && !in_array($post->post_type, $postTypes)) || !current_user_can('edit_post', $post_id) )
        {
            return;
        }

        if( $post->post_status == 'publish' )
        {
            self::updateArticleTerms($post_id, $post->post_content);
        }
        else
        {
            global $wpdb;
            /*
             * Clear the related terms
             */
            $wpdb->query("DELETE FROM " . $wpdb->prefix . self::TABLENAME . " WHERE articleId=" . $post_id);
        }
    }

    public static function getRelatedArticles($footnoteId, $limit = 5, $type = 'all')
    {
        global $wpdb;
        $where = '';

        if( $type == 'footnote' )
        {
            $where = 'WHERE p.post_type=\'footnote\'';
        }
        elseif( $type == 'others' )
        {
            $where = 'WHERE p.post_type<>\'footnote\'';
        }
        $order = get_option('cmf_footnote_relatedArticlesOrder', 'menu_order');
        $sql = $wpdb->prepare("SELECT p.ID, p.post_title, p.post_type FROM {$wpdb->prefix}" . self::TABLENAME . " g JOIN {$wpdb->posts} p ON g.articleId=p.ID AND g.footnoteId=%d " . $where . " ORDER BY " . $order . " LIMIT %d", $footnoteId, $limit);
        $results = $wpdb->get_results($sql);

        foreach($results as &$result)
        {
            $result->url = get_permalink($result->ID);
        }
        return $results;
    }

    public static function getRelatedFootnotes($articleId, $limit = 20)
    {
        global $wpdb;

        $sql = $wpdb->prepare("SELECT p.ID, p.post_title, p.post_type FROM {$wpdb->prefix}" . self::TABLENAME . " g JOIN {$wpdb->posts} p ON g.footnoteId=p.ID AND g.articleId=%d  LIMIT %d", $articleId, $limit);
        $results = $wpdb->get_results($sql);

        foreach($results as &$result)
        {
            $result->url = get_permalink($result->ID);
        }
        return $results;
    }

    public static function getCustomRelatedArticles($footnoteId)
    {
        $results = array();
        $footnote_cra = get_post_meta($footnoteId, '_footnote_related_article');
        foreach($footnote_cra as $gc)
        {
            $current_row = new stdClass;
            $current_row->ID = 1;
            $current_row->post_title = $gc['name'];
            $current_row->post_type = 'custom_related_article';
            $current_row->url = $gc['url'];
            $results[] = $current_row;
        }
        return $results;
    }

    public static function renderRelatedArticles($footnoteId, $limitArticles = 5, $heading = true)
    {
        $html = '';
        $basicArticlesType = 'all';

        $basic_articles = self::getRelatedArticles($footnoteId, $limitArticles, $basicArticlesType);
        $custom_related_articles = self::getCustomRelatedArticles($footnoteId);
        $articles = array_merge($custom_related_articles, $basic_articles);
        $tag = $heading ? 'h4' : 'div';

        if( count($articles) > 0 )
        {
            $html = '<' . $tag . ' class="footnote_related_title">' . CMF_Pro::__(get_option('cmf_footnote_showRelatedArticlesTitle')) . ' </' . $tag . '>';
            $html .= '<ul class="footnote_related">';
            foreach($articles as $article)
            {
                $title = $article->post_title;
                if( get_option('cmf_footnote_relatedArticlesPrefix') && $article->post_type == 'footnote' )
                {
                    $title = get_option('cmf_footnote_relatedArticlesPrefix') . ' ' . $title;
                }
                $target = ($article->post_type == 'custom_related_article') ? 'target="_blank"' : '';
                $html.= '<li><a href="' . $article->url . '"' . $target . '>' . $title . '</a></li>';
            }
            $html.= '</ul>';
        }

        return $html;
    }

    public static function renderRelatedFootnotes($footnoteId, $limitArticles = 5, $limitFootnote = 5, $heading = true)
    {
        $html = '';

        $footnoteArticles = self::getRelatedFootnotes($footnoteId);
        $tag = $heading ? 'h4' : 'div';

        if( count($footnoteArticles) > 0 )
        {
            $html .= '<ul class="footnote_related">';
            foreach($footnoteArticles as $article)
            {
                $title = $article->post_title;
                $html.= '<li><a href="' . $article->url . '">' . $title . '</a></li>';
            }
            $html.= '</ul>';
        }

        return $html;
    }

}
add_action('footnote_daily_event', array('CMF_Related', 'crawlArticles'));
