<?php
if( !function_exists('cminds_parse_php_info') )
{

    function cminds_parse_php_info()
    {
        $obstartresult = ob_start();
        if( $obstartresult )
        {
            $phpinforesult = phpinfo(INFO_MODULES);
            if( $phpinforesult == FALSE )
            {
                return array();
            }
            $s = ob_get_clean();
        }
        else
        {
            return array();
        }

        $s = strip_tags($s, '<h2><th><td>');
        $s = preg_replace('/<th[^>]*>([^<]+)<\/th>/', "<info>\\1</info>", $s);
        $s = preg_replace('/<td[^>]*>([^<]+)<\/td>/', "<info>\\1</info>", $s);
        $vTmp = preg_split('/(<h2>[^<]+<\/h2>)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
        $vModules = array();
        for($i = 1; $i < count($vTmp); $i++)
        {
            if( preg_match('/<h2>([^<]+)<\/h2>/', $vTmp[$i], $vMat) )
            {
                $vName = trim($vMat[1]);
                $vTmp2 = explode("\n", $vTmp[$i + 1]);
                foreach($vTmp2 AS $vOne)
                {
                    $vPat = '<info>([^<]+)<\/info>';
                    $vPat3 = "/$vPat\s*$vPat\s*$vPat/";
                    $vPat2 = "/$vPat\s*$vPat/";
                    if( preg_match($vPat3, $vOne, $vMat) )
                    { // 3cols
                        $vModules[$vName][trim($vMat[1])] = array(trim($vMat[2]), trim($vMat[3]));
                    }
                    elseif( preg_match($vPat2, $vOne, $vMat) )
                    { // 2cols
                        $vModules[$vName][trim($vMat[1])] = trim($vMat[2]);
                    }
                }
            }
        }
        return $vModules;
    }

}

if( !function_exists('cminds_file_exists_remote') )
{

    /**
     * Checks whether remote file exists
     * @param type $url
     * @return boolean
     */
    function cminds_file_exists_remote($url)
    {
        if( !function_exists('curl_version') )
        {
            return false;
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        /*
         * Don't wait more than 5s for a file
         */
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        //Check connection only
        $result = curl_exec($curl);
        //Actual request
        $ret = false;
        if( $result !== false )
        {
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            //Check HTTP status code
            if( $statusCode == 200 )
            {
                $ret = true;
            }
        }
        curl_close($curl);
        return $ret;
    }

}

if( !function_exists('cminds_sort_WP_posts_by_title_length') )
{

    function cminds_sort_WP_posts_by_title_length($a, $b)
    {
        $sortVal = 0;
        if( property_exists($a, 'post_title') && property_exists($b, 'post_title') )
        {
            $sortVal = strlen($b->post_title) - strlen($a->post_title);
        }
        return $sortVal;
    }

}

if( !function_exists('cminds_strip_only') )
{

    /**
     * Strips just one tag
     * @param type $str
     * @param type $tags
     * @param type $stripContent
     * @return type
     */
    function cminds_strip_only($str, $tags, $stripContent = false)
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

}

if( !function_exists('cminds_truncate') )
{

    /**
     * From: http://stackoverflow.com/a/2398759/2107024
     * @param type $text
     * @param type $length
     * @param type $ending
     * @param type $exact
     * @param type $considerHtml
     * @return string
     */
    function cminds_truncate($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true)
    {
        if( is_array($ending) )
        {
            extract($ending);
        }
        if( $considerHtml )
        {
            if( mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length )
            {
                return $text;
            }
            $totalLength = mb_strlen($ending);
            $openTags = array();
            $truncate = '';
            $tags = array(); //inistialize empty array
            preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
            foreach($tags as $tag)
            {
                if( !preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2]) )
                {
                    $closeTag = array();

                    if( preg_match('/<[\w]+[^>]*>/s', $tag[0]) )
                    {
                        array_unshift($openTags, $tag[2]);
                    }
                    else if( preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag) )
                    {
                        $pos = array_search($closeTag[1], $openTags);
                        if( $pos !== false )
                        {
                            array_splice($openTags, $pos, 1);
                        }
                    }
                }
                $truncate .= $tag[1];

                $contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
                if( $contentLength + $totalLength > $length )
                {
                    $left = $length - $totalLength;
                    $entitiesLength = 0;
                    $entities = array();
                    if( preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE) )
                    {
                        foreach($entities[0] as $entity)
                        {
                            if( $entity[1] + 1 - $entitiesLength <= $left )
                            {
                                $left--;
                                $entitiesLength += mb_strlen($entity[0]);
                            }
                            else
                            {
                                break;
                            }
                        }
                    }

                    $truncate .= mb_substr($tag[3], 0, $left + $entitiesLength);
                    break;
                }
                else
                {
                    $truncate .= $tag[3];
                    $totalLength += $contentLength;
                }
                if( $totalLength >= $length )
                {
                    break;
                }
            }
        }
        else
        {
            if( mb_strlen($text) <= $length )
            {
                return $text;
            }
            else
            {
                $truncate = mb_substr($text, 0, $length - strlen($ending));
            }
        }
        if( !$exact )
        {
            $spacepos = mb_strrpos($truncate, ' ');
            if( isset($spacepos) )
            {
                if( $considerHtml )
                {
                    $bits = mb_substr($truncate, $spacepos);
                    $droppedTags = array();
                    preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
                    if( !empty($droppedTags) )
                    {
                        foreach($droppedTags as $closingTag)
                        {
                            if( !in_array($closingTag[1], $openTags) )
                            {
                                array_unshift($openTags, $closingTag[1]);
                            }
                        }
                    }
                }
                $truncate = mb_substr($truncate, 0, $spacepos);
            }
        }

        $truncate .= $ending;

        if( $considerHtml )
        {
            foreach($openTags as $tag)
            {
                $truncate .= '</' . $tag . '>';
            }
        }

        return $truncate;
    }

}

if( !function_exists('cminds_show_message') )
{

    /**
     * Generic function to show a message to the user using WP's
     * standard CSS classes to make use of the already-defined
     * message colour scheme.
     *
     * @param $message The message you want to tell the user.
     * @param $errormsg If true, the message is an error, so use
     * the red message style. If false, the message is a status
     * message, so use the yellow information message style.
     */
    function cminds_show_message($message, $errormsg = false)
    {
        if( $errormsg )
        {
            echo '<div id="message" class="error">';
        }
        else
        {
            echo '<div id="message" class="updated fade">';
        }

        echo "<p><strong>$message</strong></p></div>";
    }

}

if( !function_exists('cminds_units2bytes') )
{

    /**
     * Converts the Apache memory values to number of bytes ini_get('upload_max_filesize') or ini_get('post_max_size')
     * @param type $str
     * @return type
     */
    function cminds_units2bytes($str)
    {
        $units = array('B', 'K', 'M', 'G', 'T');
        $unit = preg_replace('/[0-9]/', '', $str);
        $unitFactor = array_search(strtoupper($unit), $units);
        if( $unitFactor !== false )
        {
            return preg_replace('/[a-z]/i', '', $str) * pow(2, 10 * $unitFactor);
        }
    }

}


if( !function_exists('cminds_cmf_settings_footnote_tab_content_after') )
{

    function cminds_cmf_settings_footnote_tab_content_after($content)
    {
        ob_start();
        ?>
        <div class="block">
            <h3>Footnote - Styling</h3>
            <table class="floated-form-table form-table">
                <tr valign="top">
                    <th scope="row">Footnote link anchor style:</th>
                    <td>
                        Size: <input type="text" name="cmf_footnoteSymbolLinkAnchorSize" value="<?php echo get_option('cmf_footnoteSymbolLinkAnchorSize'); ?>" /></td>
                    <td colspan="2" class="cmf_field_help_container">Set the style of footnote link anchor</td>
                </tr>
                <tr valign="top">
                    <th scope="row">Footnote title style:</th>
                    <td>
                        Size: <input type="text" name="cmf_footnoteTitleSize" value="<?php echo get_option('cmf_footnoteTitleSize'); ?>" /></td>
                    <td colspan="2" class="cmf_field_help_container">Set the style of footnote title (if set)</td>
                </tr>
                <tr valign="top">
                    <th scope="row">Footnote description style:</th>
                    <td>
                        Size: <input type="text" name="cmf_footnoteDescriptionSize" value="<?php echo get_option('cmf_footnoteDescriptionSize'); ?>" /></td>
                    <td colspan="2" class="cmf_field_help_container">Set the style of footnote description</td>
                </tr>
                <tr valign="top">
                    <th scope="row">Footnote description show border?</th>
                    <td>
                        <input type="hidden" name="cmf_footnoteDescriptionBorder" value="0" />
                        <input type="checkbox" name="cmf_footnoteDescriptionBorder" <?php checked(true, get_option('cmf_footnoteDescriptionBorder')); ?> value="1" />
                    </td>
                    <td colspan="2" class="cmf_field_help_container">Select if you'd like to divide the footnote descriptions with a top/bottom border.</td>
                </tr>
            </table>
        </div>
        <?php
        $content = ob_get_clean();
        return $content;
    }

}

if( !function_exists('cmf_cminds_dropdown') )
{

    /**
     * Retrieve or display list of pages as a dropdown (select list).
     *
     * @since 2.1.0
     *
     * @param array|string $args Optional. Override default arguments.
     * @return string HTML content, if not displaying.
     */
    function cmf_cminds_dropdown($args = '')
    {
        $defaults = array(
            'depth'                 => 0, 'child_of'              => 0,
            'selected'              => 0, 'echo'                  => 1,
            'name'                  => 'page_id', 'id'                    => '',
            'show_option_none'      => '', 'show_option_no_change' => '',
            'option_none_value'     => '', 'add_posts'             => 1, 'post_type'             => 'page'
        );

        $r = wp_parse_args($args, $defaults);

        $pages = get_pages($r);
        $output = '';
        // Back-compat with old system where both id and name were based on $name argument
        if( empty($r['id']) )
        {
            $r['id'] = $r['name'];
        }

        if( !empty($pages) )
        {
            $output = "<select name='" . esc_attr($r['name']) . "' id='" . esc_attr($r['id']) . "'>\n";
            if( $r['show_option_no_change'] )
            {
                $selected = ( '-1' == $r['selected'] ) ? ' selected="selected"' : '';
                $output .= "\t<option value=\"-1\" {$selected} >" . $r['show_option_no_change'] . "</option>\n";
            }
            if( $r['show_option_none'] )
            {
                $selected = ( esc_attr($r['option_none_value']) == $r['selected'] ) ? ' selected="selected"' : '';
                $output .= "\t<option value=\"" . esc_attr($r['option_none_value']) . '" ' . $selected . ' >' . $r['show_option_none'] . "</option>\n";
            }
            $output .= '<optgroup label="Pages">';
            $output .= walk_page_dropdown_tree($pages, $r['depth'], $r);
            $output .= '</optgroup>';

            if( $r['add_posts'] )
            {
                $postsArgs = array();
                $posts = get_posts($postsArgs);
                if( !empty($posts) )
                {
                    $output .= '<optgroup label="Posts">';
                    $output .= cminds_walk_post_dropdown_tree($posts, $r['depth'], $r);
                    $output .= '</optgroup>';
                }
            }
            $output .= "</select>\n";
        }

        /**
         * Filter the HTML output of a list of pages as a drop down.
         *
         * @since 2.1.0
         *
         * @param string $output HTML output for drop down list of pages.
         */
        $html = apply_filters('wp_dropdown_pages', $output);

        if( $r['echo'] )
        {
            echo $html;
        }
        return $html;
    }

}

if( !class_exists('Cminds_Walker_PostDropdown') )
{

    class Cminds_Walker_PostDropdown extends Walker
    {
        /**
         * @see Walker::$tree_type
         * @since 2.1.0
         * @var string
         */
        var $tree_type = 'post';
        /**
         * @see Walker::$db_fields
         * @since 2.1.0
         * @todo Decouple this
         * @var array
         */
        var $db_fields = array('parent' => 'post_parent', 'id' => 'ID');

        /**
         * @see Walker::start_el()
         * @since 2.1.0
         *
         * @param string $output Passed by reference. Used to append additional content.
         * @param object $page Page data object.
         * @param int $depth Depth of page in reference to parent pages. Used for padding.
         * @param array $args Uses 'selected' argument for selected page to set selected HTML attribute for option element.
         */
        function start_el(&$output, $object, $depth = 0, $args = array(), $current_object_id = 0)
        {
            $pad = str_repeat('&nbsp;', $depth * 3);

            $value = ((isset($args['jump_to']) && $args['jump_to']) ? get_permalink($object->ID) : $object->ID);
            $output .= "\t<option class=\"level-$depth\" value=\"$value\"";
            if( $object->ID == $args['selected'] ) $output .= ' selected="selected"';
            $output .= '>';
            $title = esc_html($object->post_title);
            $output .= "$pad$title";
            $output .= "</option>\n";
        }

    }
}

if( !function_exists('cminds_walk_post_dropdown_tree') )
{

    function cminds_walk_post_dropdown_tree()
    {
        $args = func_get_args();
        if( empty($args[2]['walker']) )
        { // the user's options are the third parameter
            $walker = new Cminds_Walker_PostDropdown;
        }
        else
        {
            $walker = $args[2]['walker'];
        }
        return call_user_func_array(array(&$walker, 'walk'), $args);
    }

}