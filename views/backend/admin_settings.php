<?php if( !empty($messages) ): ?>
    <div class="updated" style="clear:both"><p><?php echo $messages; ?></p></div>
<?php endif; ?>

<br/>

<br/>

<div class="cminds_settings_description">
    <p>
        <strong>Supported Shortcodes:</strong> <a href="javascript:void(0)" onclick="jQuery(this).parent().next().slideToggle()">Show/Hide</a>
    </p>

    <ul style="display:none;list-style-type:disc;margin-left:20px;">
        <li><strong>Apply footnote</strong> - [cm_footnote_parse] text [/cm_footnote_parse] <sup>1</sup></li>
        <li><del><strong>Custom footnote</strong> - [cm_custom_footnote id="custom_footnote_id"] text [/cm_custom_footnote]</del> - Only in <a href="<?php echo CMF_RELEASE_NOTES; ?>"  target="_blank">Pro</a></li>
        <li><del><strong>Exclude from parsing</strong> - [footnote_exclude] text [/footnote_exclude]</del> - Only in <a href="<?php echo CMF_RELEASE_NOTES; ?>"  target="_blank">Pro</a></li>
        <li><del><strong>Show Footnote Index</strong> - [footnote search_footnote="footnote" <sup>2</sup>itemspage="1" <sup>2</sup>letter="all" ]</del> - Only in <a href="<?php echo CMF_RELEASE_NOTES; ?>"  target="_blank">Pro</a></li>
        <li>
            <sup>1</sup> The shortcode internally calls custom filter called 'cm_footnote_parse' which can be used if you want the footnote funtionality outside of 'the_content':
            <code>$text_with_footnote = apply_filter('cm_footnote_parse', $text);</code>
        </li>
        <li>
            <sup>2</sup> This attribute is for Server-side pagination only
        </li>
    </ul>
    <form method="post">
        <div>
            <div class="cmf_field_help_container">Warning! This option will completely erase all of the data stored by the CM Footnotes in the database: footnotes, options, synonyms etc. <br/> It will also remove the Footnote Index Page. <br/> It cannot be reverted.</div>
            <input onclick="return confirm('All database items of CM Footnotes (footnotes, options etc.) will be erased. This cannot be reverted.')" type="submit" name="cmf_footnotePluginCleanup" value="Cleanup database" class="button cmf-cleanup-button"/>
            <span style="display: inline-block;position: relative;"></span>
        </div>
    </form>

    <?php
// check permalink settings
    if( get_option('permalink_structure') == '' )
    {
        echo '<span style="color:red">Your WordPress Permalinks needs to be set to allow plugin to work correctly. Please Go to <a href="' . admin_url() . 'options-permalink.php" target="new">Settings->Permalinks</a> to set Permalinks to Post Name.</span><br><br>';
    }
    ?>

</div>

<?php
include plugin_dir_path(__FILE__) . '/call_to_action.phtml';
?>

<br/>
<div class="clear"></div>

<form method="post">
    <?php wp_nonce_field('update-options'); ?>
    <input type="hidden" name="action" value="update" />


    <div id="cmf_tabs" class="footnoteSettingsTabs">
        <div class="footnote_loading"></div>

        <?php
        CMF_Pro::renderSettingsTabsControls();

        CMF_Pro::renderSettingsTabs();
        ?>

        <div id="tabs-1">
            <div class="block">
                <h3>Footnotes display</h3>
                <table class="floated-form-table form-table">
                    <tr valign="top">
                        <th scope="row">Display footnotes on given post types:</th>
                        <td>
                            <input type="hidden" name="cmf_footnoteOnPosttypes" value="0" />
                            <?php
                            echo CMF_Pro::outputCustomPostTypesList();
                            ?>
                        </td>
                        <td colspan="2" class="cmf_field_help_container">Select the custom post types where you'd like the Footnote Terms to be highlighted.</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Only show footnotes on single posts/pages (not Homepage, authors etc.)?</th>
                        <td>
                            <input type="hidden" name="cmf_footnoteOnlySingle" value="0" />
                            <input type="checkbox" name="cmf_footnoteOnlySingle" <?php checked(true, get_option('cmf_footnoteOnlySingle')); ?> value="1" />
                        </td>
                        <td colspan="2" class="cmf_field_help_container">Select this option if you wish to only highlight footnotes when viewing a single page/post.
                            This can be used so footnotes aren't highlighted on your homepage, or author pages and other taxonomy related pages.</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Highlight first footnote occurance only?</th>
                        <td>
                            <input type="hidden" name="cmf_footnoteFirstOnly" value="0" />
                            <input type="checkbox" name="cmf_footnoteFirstOnly" <?php checked(true, get_option('cmf_footnoteFirstOnly')); ?> value="1" />
                        </td>
                        <td colspan="2" class="cmf_field_help_container">Select this option if you want to only highlight the first occurance of each footnote on a page/post.</td>
                    </tr>
                </table>
                <div class="clear"></div>
            </div>
            <div class="block">
                <h3>Performance &amp; Debug</h3>
                <table class="floated-form-table form-table">
                    <tr valign="top">
                        <th scope="row">Only highlight on "main" WP query?</th>
                        <td>
                            <input type="hidden" name="cmf_footnoteOnMainQuery" value="0" />
                            <input type="checkbox" name="cmf_footnoteOnMainQuery" <?php checked(1, get_option('cmf_footnoteOnMainQuery')); ?> value="1" />
                        </td>
                        <td colspan="2" class="cmf_field_help_container">
                            <strong>Warning: Don't change this setting unless you know what you're doing</strong><br/>
                            Select this option if you wish to only highlight footnotes on main footnote query.
                            Unchecking this box may fix problems with highlighting footnotes on some themes which manipulate the WP_Query.</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Enable the caching mechanisms</th>
                        <td>
                            <input type="hidden" name="cmf_footnoteEnableCaching" value="0" />
                            <input type="checkbox" name="cmf_footnoteEnableCaching" <?php checked(true, get_option('cmf_footnoteEnableCaching', TRUE)); ?> value="1" />
                        </td>
                        <td colspan="2" class="cmf_field_help_container">Select this option if you want to use the internal caching mechanisms.</td>
                    </tr>
                </table>
                <div class="clear"></div>
            </div>
            <div class="block">
                <h3>Referrals</h3>
                <p>Refer new users to any of the CM Plugins and you'll receive a minimum of <strong>15%</strong> of their purchase! For more information please visit CM Plugins <a href="http://www.cminds.com/referral-program/" target="new">Affiliate page</a></p>
                <table>
                    <tr valign="top">
                        <th scope="row" valign="middle" align="left" >Enable referrals:</th>
                        <td>
                            <input type="hidden" name="cmf_footnoteReferral" value="0" />
                            <input type="checkbox" name="cmf_footnoteReferral" <?php checked(1, get_option('cmf_footnoteReferral')); ?> value="1" />
                        </td>
                        <td colspan="2" class="cmf_field_help_container">Enable referrals link at the bottom of the question and the answer page<br><br></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" valign="middle" align="left" ><?php CMF_Pro::_e('Affiliate Code'); ?>:</th>
                        <td>
                            <input type="text" name="cmf_footnoteAffiliateCode" value="<?php echo get_option('cmf_footnoteAffiliateCode'); ?>" placeholder="<?php CMF_Pro::_e('Affiliate Code'); ?>"/>
                        </td>
                        <td colspan="2" class="cmf_field_help_container"><?php CMF_Pro::_e('Please add your affiliate code in here.'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <div id="tabs-3">
            <div class="block">
                <h3>Footnote - Parsing</h3>
                <table class="floated-form-table form-table">
                    <tr valign="top">
                        <th scope="row">Avoid parsing protected tags?</th>
                        <td>
                            <input type="hidden" name="cmf_footnoteProtectedTags" value="0" />
                            <input type="checkbox" name="cmf_footnoteProtectedTags" <?php checked(true, get_option('cmf_footnoteProtectedTags')); ?> value="1" />
                        </td>
                        <td colspan="2" class="cmf_field_help_container">Select this option if you want to avoid searching for the footnotes for the following tags: Script, A, H1, H2, H3, PRE, Object.</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Terms case-sensitive?</th>
                        <td>
                            <input type="hidden" name="cmf_footnoteCaseSensitive" value="0" />
                            <input type="checkbox" name="cmf_footnoteCaseSensitive" <?php checked('1', get_option('cmf_footnoteCaseSensitive')); ?> value="1" />
                        </td>
                        <td colspan="2" class="cmf_field_help_container">Select this option if you want footnotes to be case-sensitive.</td>
                    </tr>
                </table>
            </div>
            <div class="block">
                <h3>Footnote - Links</h3>
                <table class="floated-form-table form-table">
                    <tr valign="top">
                        <th scope="row">Show HTML "title" attribute for footnote links</th>
                        <td>
                            <input type="hidden" name="cmf_showTitleAttribute" value="0" />
                            <input type="checkbox" name="cmf_showTitleAttribute" <?php checked(true, get_option('cmf_showTitleAttribute')); ?> value="1" />
                        </td>
                        <td colspan="2" class="cmf_field_help_container">Select this option if you want to use footnote name as HTML "title" for link</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Footnote link symbol:</th>
                        <td>Size: <input type="text" name="cmf_footnoteSymbolSize" value="<?php echo get_option('cmf_footnoteSymbolSize'); ?>" /></td>
                        <td colspan="2" class="cmf_field_help_container">Set the style of footnote link.</td>
                    </tr>
                </table>
            </div>
            <div class="block">
                <h3>Footnote - Related Articles</h3>
                <table class="floated-form-table form-table">
                    <tr valign="top">
                        <th scope="row">Order of the related articles by:</th>
                        <td>
                            <select name="cmf_footnote_relatedArticlesOrder">
                                <option value="menu_order" <?php selected('menu_order', get_option('cmf_footnote_relatedArticlesOrder')); ?>>Menu Order</option>
                                <option value="post_title" <?php selected('post_title', get_option('cmf_footnote_relatedArticlesOrder')); ?>>Post Title</option>
                                <option value="post_date DESC" <?php selected('post_date DESC', get_option('cmf_footnote_relatedArticlesOrder')); ?>>Publising Date DESC</option>
                                <option value="post_date ASC" <?php selected('post_date ASC', get_option('cmf_footnote_relatedArticlesOrder')); ?>>Publising Date ASC</option>
                                <option value="post_modified DESC" <?php selected('post_modified DESC', get_option('cmf_footnote_relatedArticlesOrder')); ?>>Last Modified DESC</option>
                                <option value="post_modified ASC" <?php selected('post_modified ASC', get_option('cmf_footnote_relatedArticlesOrder')); ?>>Last Modified ASC</option>
                            </select>
                        </td>
                        <td colspan="2" class="cmf_field_help_container">How the related articles should be ordered?</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Title of related articles:</th>
                        <td><input  type="text" name="cmf_footnote_showRelatedArticlesTitle" value="<?php echo get_option('cmf_footnote_showRelatedArticlesTitle'); ?>" /></td>
                        <td colspan="2" class="cmf_field_help_container">What should be the title of related articles widget?</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Post types to index:</th>
                        <td><select multiple name="cmf_footnote_showRelatedArticlesPostTypesArr[]" >
                                <?php
                                $types = get_option('cmf_footnote_showRelatedArticlesPostTypesArr');
                                foreach(get_post_types() as $type):
                                    ?>
                                    <option value="<?php echo $type; ?>" <?php if( is_array($types) && in_array($type, $types) ) echo 'selected'; ?>><?php echo $type; ?></option>
                                <?php endforeach; ?>
                            </select></td>
                        <td colspan="2" class="cmf_field_help_container">Which post types should be indexed? (select more by holding down ctrl key)</td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Refresh related articles index:</th>
                        <td><input type="submit" name="cmf_footnoteRelatedRefresh" value="Rebuild Index!" class="button"/></td>
                        <td colspan="2" class="cmf_field_help_container">The index for relations between articles (posts, pages) and footnotes is being rebuilt on daily basis. Click this button if you want to do it manually (it may take a while)</td>
                    </tr>

                </table>
            </div>
            <div class="block">
                <h3>Footnote - Synonyms</h3>
                <table class="floated-form-table form-table">
                    <tr valign="top">
                        <th scope="row">Show synonyms list in footnote</th>
                        <td>
                            <input type="hidden" name="cmf_footnote_addSynonymsFootnote" value="0" />
                            <input type="checkbox" name="cmf_footnote_addSynonymsFootnote" <?php checked(true, get_option('cmf_footnote_addSynonymsFootnote')); ?> value="1" />
                        </td>
                        <td colspan="2" class="cmf_field_help_container">Select this option if you want to show the list of synonyms of the footnote</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Title of synonyms list:</th>
                        <td><input  type="text" name="cmf_footnote_addSynonymsTitle" value="<?php echo get_option('cmf_footnote_addSynonymsTitle'); ?>" /></td>
                        <td colspan="2" class="cmf_field_help_container">What should be the title of synonyms widget?</td>
                    </tr>
                </table>
            </div>
            <div class="block">
                <h3>Footnote - Content</h3>
                <table class="floated-form-table form-table">
                    <tr valign="top">
                        <th scope="row">Display the headers row in the Footnote?</th>
                        <td>
                            <input type="hidden" name="cmf_displayHeadersInFootnote" value="0" />
                            <input type="checkbox" name="cmf_displayHeadersInFootnote" <?php checked('1', get_option('cmf_displayHeadersInFootnote', '0')); ?> value="1" />
                        </td>
                        <td colspan="2" class="cmf_field_help_container">Select this option if you want to display the headers row in the footnote</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Header for the Anchor/Title:</th>
                        <td><input  type="text" name="cmf_footnoteHeaderAnchorTitle" value="<?php echo get_option('cmf_footnoteHeaderAnchorTitle'); ?>" /></td>
                        <td colspan="2" class="cmf_field_help_container">The header of the anchor/title column in the footnote</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Header for the Description</th>
                        <td><input  type="text" name="cmf_footnoteHeaderDescription" value="<?php echo get_option('cmf_footnoteHeaderDescription'); ?>" /></td>
                        <td colspan="2" class="cmf_field_help_container">The header of the description column in the footnote</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Display the footnote's title in the Footnote?</th>
                        <td>
                            <input type="hidden" name="cmf_displayTermInFootnote" value="0" />
                            <input type="checkbox" name="cmf_displayTermInFootnote" <?php checked('1', get_option('cmf_displayTermInFootnote', '0')); ?> value="1" />
                        </td>
                        <td colspan="2" class="cmf_field_help_container">Select this option if you want to display the footnote term in the footnote.</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Number of characters in the Footnote description:</th>
                        <td><input type="number" name="cmf_footnoteDescriptionCharactersCount" value="<?php echo get_option('cmf_footnoteDescriptionCharactersCount'); ?>" /></td>
                        <td colspan="2" class="cmf_field_help_container">How many characters of the description should be initially displayed. Rest of the description will be displayed on hover.</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Add footnote editlink to the footnote content?</th>
                        <td>
                            <input type="hidden" name="cmf_footnoteAddTermEditlink" value="0" />
                            <input type="checkbox" name="cmf_footnoteAddTermEditlink" <?php checked(true, get_option('cmf_footnoteAddTermEditlink')); ?> value="1" />
                        </td>
                        <td colspan="2" class="cmf_field_help_container">Select this option if you want the footnote editlink to appear in the footnote content (only for logged in users with "edit_posts" capability).</td>
                    </tr>
                </table>
            </div>
            <?php
            $additionalFootnoteTabContent = apply_filters('cmf_settings_footnote_tab_content_after', '');
            echo $additionalFootnoteTabContent;
            ?>
            <!-- Start Server information Module -->
            <div id="tabs-99">
                <div class='block'>
                    <h3>Server Information</h3>
                    <?php
                    $safe_mode = ini_get('safe_mode') ? ini_get('safe_mode') : 'Off';
                    $upload_max = ini_get('upload_max_filesize') ? ini_get('upload_max_filesize') : 'N/A';
                    $post_max = ini_get('post_max_size') ? ini_get('post_max_size') : 'N/A';
                    $memory_limit = ini_get('memory_limit') ? ini_get('memory_limit') : 'N/A';
                    $allow_url_fopen = ini_get('allow_url_fopen') ? ini_get('allow_url_fopen') : 'N/A';
                    $max_execution_time = ini_get('max_execution_time') !== FALSE ? ini_get('max_execution_time') : 'N/A';
                    $cURL = function_exists('curl_version') ? 'On' : 'Off';
                    $mb_support = function_exists('mb_strtolower') ? 'On' : 'Off';
                    $intl_support = extension_loaded('intl') ? 'On' : 'Off';

                    $php_info = cminds_parse_php_info();
                    ?>
                    <span class="description" style="">
                        CM Footnotes is a mix of  JavaScript application and a parsing engine.
                        This information is useful to check if CM Footnotes might have some incompabilities with you server
                    </span>
                    <table class="form-table server-info-table">
                        <tr>
                            <td>PHP Version</td>
                            <td><?php echo phpversion(); ?></td>
                            <td><?php if( version_compare(phpversion(), '5.3.0', '<') ): ?><strong>Recommended 5.3 or higher</strong><?php else: ?><span>OK</span><?php endif; ?></td>
                        </tr>
                        <tr>
                            <td>mbstring support</td>
                            <td><?php echo $mb_support; ?></td>
                            <td><?php if( $mb_support == 'Off' ): ?>
                                    <strong>"mbstring" library is required for plugin to work.</strong>
                                <?php else: ?><span>OK</span><?php endif; ?></td>
                        </tr>
                        <tr>
                            <td>intl support</td>
                            <td><?php echo $intl_support; ?></td>
                            <td><?php if( $intl_support == 'Off' ): ?>
                                    <strong>"intl" library is required for proper sorting of accented characters on Footnote Index page.</strong>
                                <?php else: ?><span>OK</span><?php endif; ?></td>
                        </tr>
                        <tr>
                            <td>PHP Memory Limit</td>
                            <td><?php echo $memory_limit; ?></td>
                            <td><?php if( cminds_units2bytes($memory_limit) < 1024 * 1024 * 128 ): ?>
                                    <strong>This value can be too low for a site with big footnote.</strong>
                                <?php else: ?><span>OK</span><?php endif; ?></td>
                        </tr>
                        <tr>
                            <td>PHP Max Upload Size (Pro, Pro+, Ecommerce)</td>
                            <td><?php echo $upload_max; ?></td>
                            <td><?php if( cminds_units2bytes($upload_max) < 1024 * 1024 * 5 ): ?>
                                    <strong>This value can be too low to import large files.</strong>
                                <?php else: ?><span>OK</span><?php endif; ?></td>
                        </tr>
                        <tr>
                            <td>PHP Max Post Size (Pro, Pro+, Ecommerce)</td>
                            <td><?php echo $post_max; ?></td>
                            <td><?php if( cminds_units2bytes($post_max) < 1024 * 1024 * 5 ): ?>
                                    <strong>This value can be too low to import large files.</strong>
                                <?php else: ?><span>OK</span><?php endif; ?></td>
                        </tr>
                        <tr>
                            <td>PHP Max Execution Time </td>
                            <td><?php echo $max_execution_time; ?></td>
                            <td><?php if( $max_execution_time != 0 && $max_execution_time < 300 ): ?>
                                    <strong>This value can be too low for lengthy operations. We strongly suggest setting this value to at least 300 or 0 which is no limit.</strong>
                                <?php else: ?><span>OK</span><?php endif; ?></td>
                        </tr>
                        <tr>
                            <td>PHP cURL (Pro+, Ecommerce)</td>
                            <td><?php echo $cURL; ?></td>
                            <td><?php if( $cURL == 'Off' ): ?>
                                    <strong>cURL library is required to check if remote audio file exists.</strong>
                                <?php else: ?><span>OK</span><?php endif; ?></td>
                        </tr>
                        <tr>
                            <td>PHP allow_url_fopen (Pro+, Ecommerce)</td>
                            <td><?php echo $allow_url_fopen; ?></td>
                            <td><?php if( $allow_url_fopen == '0' ): ?>
                                    <strong>allow_url_fopen is required to connect to the Merriam-Webster and Wikipedia API.</strong>
                                <?php else: ?><span>OK</span><?php endif; ?></td>
                        </tr>

                        <?php
                        if( isset($php_info['gd']) && is_array($php_info['gd']) )
                        {
                            foreach($php_info['gd'] as $key => $val)
                            {
                                if( !preg_match('/(WBMP|XBM|Freetype|T1Lib)/i', $key) && $key != 'Directive' && $key != 'gd.jpeg_ignore_warning' )
                                {
                                    echo '<tr>';
                                    echo '<td>' . $key . '</td>';
                                    if( stripos($key, 'support') === false )
                                    {
                                        echo '<td>' . $val . '</td>';
                                    }
                                    else
                                    {
                                        echo '<td>enabled</td>';
                                    }
                                    echo '</tr>';
                                }
                            }
                        }
                        ?>
                    </table>
                </div>
            </div>
        </div>
        <p class="submit" style="clear:left">
            <input type="submit" class="button-primary" value="<?php CMF_Pro::_e('Save Changes') ?>" name="cmf_footnoteSave" />
        </p>
</form>