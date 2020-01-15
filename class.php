<?php

require_once ('ice-dragon-constants.php');


class IDRA_Ice_Dragon_Paywall {

    private $plugin_name	= IDRA_PLUGIN_NAME;
    private $plugin_slug	= IDRA_PLUGIN_SLUG;
    private $basename		= IDRA_PLUGIN_BASENAME;

    /**
     * Class constructor, puts things in motion
     *
     * @since 1.0.0
     */
    function __construct() {

        $settings = $this->get_settings();

        add_action( 'admin_init', array( $this, 'upgrade' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_wp_enqueue_scripts' ) );
        add_action( 'admin_print_styles', array( $this, 'admin_wp_print_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );

        add_action( 'admin_menu', array( $this, 'admin_menu' ) );

        add_action( 'wp_ajax_ice_dragon_paywall_process_notice_link', array( $this, 'ajax_process_notice_link' ) );

        add_action( 'wp', array( $this, 'process_content_restrictions' ) );

        if ( 'on' === $settings['restrict_pdf_downloads'] ) {
            add_filter( 'issuem_pdf_attachment_url', array( $this, 'restrict_pdf_attachment_url' ), 10, 2 );
        }

    }

    public function process_content_restrictions()
    {

        if ( is_admin() ) {
            return;
        }

        $restrictions = new IDRA_Content_Restrictions();
        $restrictions->process_content_restrictions();
    }

    /**
     * Initialize pigeonpack Admin Menu
     *
     * @since 1.0.0
     * @uses add_menu_page() Creates Pigeon Pack menu
     * @uses add_submenu_page() Creates Settings submenu to Pigeon Pack menu
     * @uses add_submenu_page() Creates Help submenu to Pigeon Pack menu
     * @uses do_action() To call 'pigeonpack_admin_menu' for future addons
     */
    function admin_menu() {

        add_menu_page( __( 'Ice Dragon Paywall', 'leaky-paywall' ), __( 'Ice Dragon', 'leaky-paywall' ), apply_filters( 'manage_ice_dragon_paywall_settings', 'manage_options' ), IDRA_Constants::TOP_LEVEL_PAGE_NAME, array( $this, 'settings_page' ), IDRA_PLUGIN_URL . '/images/dragon-solid-20x20.png' ); // font-awesome: fas fa-dragon

        /*
        add_submenu_page( IDRA_Constants::TOP_LEVEL_PAGE_NAME, __( 'Settings', 'leaky-paywall' ), __( 'Settings', 'leaky-paywall' ), apply_filters( 'manage_ice_dragon_paywall_settings', 'manage_options' ), 'issuem-leaky-paywall', array( $this, 'settings_page' ) );

        add_submenu_page( IDRA_Constants::TOP_LEVEL_PAGE_NAME, __( 'Subscribers', 'leaky-paywall' ), __( 'Subscribers', 'leaky-paywall' ), apply_filters( 'manage_ice_dragon_paywall_settings', 'manage_options' ), 'leaky-paywall-subscribers', array( $this, 'subscribers_page' ) );
        */
    }




    /**
     * Prints backend IssueM styles
     *
     * @since 1.0.0
     */
    function admin_wp_print_styles()
    {

        global $hook_suffix;

        if ('toplevel_page_' . IDRA_Constants::TOP_LEVEL_PAGE_NAME === $hook_suffix
                || 'index.php' === $hook_suffix
                || 'leaky-paywall_page_leaky-paywall-addons' === $hook_suffix) {

            wp_enqueue_style('ice-dragon-paywall_admin_lpaywall-fork', IDRA_PLUGIN_URL . 'css/lpaywall-admin.css', '', IDRA_PLUGIN_VERSION);

            /* Added for Ice Dragon */
            wp_enqueue_style('ice-dragon-paywall_admin', IDRA_PLUGIN_URL . 'css/puzzle-itc-ice-dragon-admin.css', '', IDRA_PLUGIN_VERSION);
        }
    }

    /**
     * Enqueues backend IssueM styles
     *
     * @since 1.0.0
     */
    function admin_wp_enqueue_scripts( $hook_suffix ) {

        if ( 'toplevel_page_' . IDRA_Constants::TOP_LEVEL_PAGE_NAME === $hook_suffix ) {
            wp_enqueue_script( 'ice-dragon-paywall_lpaywall-fork', IDRA_PLUGIN_URL . 'js/lpaywall-settings.js', array( 'jquery' ), IDRA_PLUGIN_VERSION );
        }

    }

    /**
     * Enqueues frontend scripts and styles
     *
     * @since 1.0.0
     */
    function frontend_scripts() {

        $settings = $this->get_settings();

        if ( $settings['use_css'] === true ) {
            wp_enqueue_style('ice-dragon-paywall_lpaywall-fork', IDRA_PLUGIN_URL . '/css/lpaywall.css', '', IDRA_PLUGIN_VERSION);
            // Added for Ice Dragon
            wp_enqueue_style('ice-dragon-paywall', IDRA_PLUGIN_URL . '/css/puzzle-itc-ice-dragon.css', '', IDRA_PLUGIN_VERSION);
        }

    }

    /**
     * Get zeen101's Leaky Paywall options
     *
     * @since 1.0.0
     */
    function get_settings() {

        $defaults = array(
            'custom_excerpt_length'			=> '',
            'post_types'					=> array( 'post' ), /* Site Specific */
            'free_articles'					=> 2,
            'cookie_expiration' 			=> 24,
            'cookie_expiration_interval' 	=> 'day',
            'subscribe_login_message'		=> __( 'Use ice-dragon.ch to pay for this content over the Lightning Network ⚡', 'leaky-paywall' ),
            'pay_direct_message'		=> __( 'Tear down this Paywall! ⚡', 'leaky-paywall' ),
            'use_css'						=> true,
            'enable_user_delete_account'	=> 'off',
            'site_name'						=> get_option( 'blogname' ), /* Site Specific */
            'from_name'						=> get_option( 'blogname' ), /* Site Specific */
            'from_email'					=> get_option( 'admin_email' ), /* Site Specific */
            'test_mode'						=> 'off',
            'restrict_pdf_downloads' 		=> 'off',
            'restrictions' 	=> array(
                'post_types' => array(
                    'post_type' => 'post',
                    'taxonomy'	=> 'all',
                )
            ),
        );

        $defaults = apply_filters( 'ice_dragon_paywall_default_settings', $defaults );
        $settings = get_option( IDRA_Constants::DB_STORAGE_KEY ); /* Site specific settings */
        $settings = wp_parse_args( $settings, $defaults );

        return apply_filters( 'ice_dragon_paywall_get_settings', $settings );

    }

    /**
     * Update zeen101's Leaky Paywall options
     *
     * @since 1.0.0
     */
    function update_settings( $settings ) {
        update_option( IDRA_Constants::DB_STORAGE_KEY, $settings );
    }

    /**
     * Create and Display Leaky Paywall settings page
     *
     * @since 1.0.0
     */
    function settings_page() {

        // Get the user options
        $settings = $this->get_settings();
        $settings_saved = false;

        if(isset($_GET['tab'])) {
            $tab = trim(sanitize_text_field($_GET['tab']));
        } else if(trim(sanitize_text_field($_GET['page'])) == IDRA_Constants::TOP_LEVEL_PAGE_NAME) {
            $tab = 'appearance';
        } else {
            $tab = '';
        }

        $settings_tabs = apply_filters('ice_dragon_paywall_settings_tabs', array('appearance', 'restrictions', 'integration'));

        $current_tab = apply_filters( 'ice_dragon_paywall_current_tab', $tab, $settings_tabs );

        if ( isset( $_REQUEST['update_lpaywall_settings'] ) ) {

            if ( $current_tab === 'appearance' ) {

                if ( isset( $_POST['custom_excerpt_length'] ) ) {

                    if ( strlen( $_POST['custom_excerpt_length'] ) > 0 ) {
                        $settings['custom_excerpt_length'] = intval($_POST['custom_excerpt_length']);
                    } else {
                        $settings['custom_excerpt_length'] = '';
                    }

                }

                if ( !empty( $_REQUEST['subscribe_login_message'] ) )
                    $settings['subscribe_login_message'] = trim(sanitize_text_field($_REQUEST['subscribe_login_message']));

                if ( !empty( $_REQUEST['pay_direct_message'] ) )
                    $settings['pay_direct_message'] = trim(sanitize_text_field($_REQUEST['pay_direct_message']));

                $settings['use_css'] = !empty($_REQUEST['use_css']);

            }

            if ( $current_tab === 'restrictions' ) {

                if ( !empty( $_REQUEST['post_types'] ) )
                    $settings['post_types'] = sanitize_text_field($_REQUEST['post_types']);

                // not implemented for now. If this will be implemented, remember to secure input and output:
                // https://developer.wordpress.org/plugins/security/securing-input/
                // https://developer.wordpress.org/plugins/security/securing-output/
//					if ( !empty( $_REQUEST['restrict_pdf_downloads'] ) )
//						$settings['restrict_pdf_downloads'] = $_REQUEST['restrict_pdf_downloads'];
//					else
//						$settings['restrict_pdf_downloads'] = 'off';

                if(!empty($_REQUEST['restrictions'])){

                    $restrictions = array();

                    // sanitize input data
                    foreach ($_REQUEST['restrictions']['post_types'] as $key => $restriction) {
                        $keySanitized = intval($key);
                        $restrictions['post_types'][$keySanitized]['post_type'] = sanitize_text_field($restriction['post_type']);
                        $restrictions['post_types'][$keySanitized]['taxonomy'] = sanitize_text_field($restriction['taxonomy']);
                    }

                    $settings['restrictions'] = $restrictions;
                } else {
                    $settings['restrictions'] = array();
                }

                if ( isset( $_POST['js_restrictions_post_container'] ) ) {
                    $settings['js_restrictions_post_container'] = sanitize_text_field( $_POST['js_restrictions_post_container'] );
                }

                if ( isset( $_POST['js_restrictions_page_container'] ) ) {
                    $settings['js_restrictions_page_container'] = sanitize_text_field( $_POST['js_restrictions_page_container'] );
                }

            }

            if ( $current_tab === 'integration' ) {

                if ( !empty( $_REQUEST[IDRA_Constants::SETTINGS_KEY_HMAC_SECRET] ) )
                    $settings[IDRA_Constants::SETTINGS_KEY_HMAC_SECRET] = trim(sanitize_text_field($_REQUEST[IDRA_Constants::SETTINGS_KEY_HMAC_SECRET]));

            }

            $settings = apply_filters( 'ice_dragon_paywall_update_settings_settings', $settings, $current_tab );
            $this->update_settings( $settings );
            $settings_saved = true;

            do_action( 'ice_dragon_paywall_update_settings', $settings );

        }

        if ( $settings_saved ) {

            // update settings notification ?>
            <div class="updated"><p><strong><?php _e( "Settings Updated", 'leaky-paywall' );?></strong></p></div>
            <?php

        }

        // Display HTML form for the options below
        ?>
        <div class=wrap>
        <div style="width:70%;" class="postbox-container">
        <div class="metabox-holder">
        <div class="meta-box-sortables ui-sortable">

            <form id="issuem" method="post" action="">

                <h1 style='margin-bottom: 2px;' ><?php _e( "Ice Dragon Paywall", 'leaky-paywall' ); ?></h1>

                    <?php
                    if ( in_array($current_tab, $settings_tabs) )
                        {
                        ?>
                        <h2 class="nav-tab-wrapper" style="margin-bottom: 10px;">

                            <a href="<?php echo admin_url('admin.php?page=' . IDRA_Constants::TOP_LEVEL_PAGE_NAME);?>" class="nav-tab<?php if($current_tab == 'appearance') { ?> nav-tab-active<?php } ?>"><?php _e('Appearance', 'leaky-paywall');?></a>

                            <a href="<?php echo admin_url('admin.php?page=' . IDRA_Constants::TOP_LEVEL_PAGE_NAME . '&tab=restrictions');?>" class="nav-tab<?php if($current_tab == 'restrictions') { ?> nav-tab-active<?php } ?>"><?php _e('Content Restriction', 'leaky-paywall');?></a>

                            <a href="<?php echo admin_url('admin.php?page=' . IDRA_Constants::TOP_LEVEL_PAGE_NAME . '&tab=integration');?>" class="nav-tab<?php if($current_tab == 'integration') { ?> nav-tab-active<?php } ?>"><?php _e('Integration', 'leaky-paywall');?></a>

                            <?php /* Added for Ice Dragon */ ?>
                            <a href="https://ice-dragon.ch" target="_blank">
                                <img src="<?php echo IDRA_PLUGIN_URL . '/images/iceDragonLogo.png' ?>" alt="Ice Dragon Logo" id="ice-dragon-logo">
                            </a>
                        </h2>
                    <?php } // endif ?>

                    <?php do_action('ice_dragon_paywall_before_settings', $current_tab ); ?>

                    <?php if ( $current_tab == 'appearance' ) : ?>

                    <?php do_action('ice_dragon_paywall_before_general_settings'); ?>

                    <div id="modules" class="postbox">

                        <div class="handlediv" title="Click to toggle"><br /></div>

                        <h3 class="hndle"><span><?php _e( 'Appearance of the Paywall', 'leaky-paywall' ); ?></span></h3>

                        <div class="inside">

                            <?php if ( !isset( $settings['page_for_subscription'] ) || !$settings['page_for_subscription'] ) {
                                ?>
                                <p>Need help getting started?
                                    <?php /* todo write documentation
                                    <a target="_blank" href="https://github.com/ln-ice-dragon/ice-dragon-wp-plugin">See our guide</a>
or */ ?>
                                    <a target="_blank" href="https://puzzle.ch/lightning">Contact us</a>.</p>
                                <?php
                            } ?>

                        <table id="lpaywall_administrator_options" class="form-table">
                            <tr>
                                <th><?php _e( 'Custom Excerpt Length', 'leaky-paywall' ); ?></th>
                                <td>
                                    <input type="number" id="custom_excerpt_length" class="small-text" name="custom_excerpt_length" value="<?php echo esc_attr( $settings['custom_excerpt_length'] ); ?>">
                                    <p class="description">
                                        <?php _e( "Amount of content (in characters) to show before displaying the subscribe nag. If nothing is entered then the full excerpt is displayed.", 'leaky-paywall' ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th><?php _e( 'Login Necessary Message', 'leaky-paywall' ); ?></th>
                                <td>
                                    <textarea id="subscribe_login_message" class="large-text" name="subscribe_login_message" cols="50" rows="3"><?php echo esc_html($settings['subscribe_login_message']); ?></textarea>
                                    <p class="description">
                                        <?php _e( "Is shown when a visitor does not have an active session with the Ice Dragon website. The user has to login first.", 'leaky-paywall' ); ?>
                                    </p>
                                </td>
                            </tr>

            <?php /* Todo implement this

                            <tr>
                                <th><?php _e( 'Direct Pay Message', 'leaky-paywall' ); ?></th>
                                <td>
                                    <textarea id="pay_direct_message" class="large-text" name="pay_direct_message" cols="50" rows="3"><?php echo stripslashes( $settings['pay_direct_message'] ); ?></textarea>
                                    <p class="description">
                                    <?php _e( "Is shown when a visitor has an active session with the Ice Dragon website. Below a QR code of an Invoice is shown for the user to pay.", 'leaky-paywall' ); ?>
                                    </p>
                                </td>
                            </tr>

*/ ?>

                            <tr>
                                <th><?php _e( 'Use default CSS Style', 'leaky-paywall' ); ?></th>
                                <td>
                                    <input type="checkbox" name="use_css" id="use_css" value="true"<?php if($settings['use_css']){echo " checked";}?> >
                                </td>
                            </tr>

                            <?php wp_nonce_field( 'pitc_ice_dragon_general_options', 'pitc_ice_dragon_general_options_nonce' ); ?>

                        </table>

                        </div>

                    </div>

                    <?php do_action('ice_dragon_paywall_after_general_settings'); ?>

                    <?php do_action( 'ice_dragon_paywall_settings_form', $settings ); // here for backwards compatibility ?>

                    <p class="submit">
                        <input class="button-primary" type="submit" name="update_lpaywall_settings" value="<?php _e( 'Save Settings', 'leaky-paywall' ) ?>" />
                    </p>

                    <?php endif; ?>

                    <?php if ( $current_tab == 'restrictions' ) : ?>

                    <?php do_action('ice_dragon_paywall_before_restrictions_settings'); ?>

                    <div id="modules" class="postbox leaky-paywall-restriction-settings">

                        <div class="handlediv" title="Click to toggle"><br /></div>

                        <h3 class="hndle"><span><?php _e( 'Restrict Content via Paywall', 'leaky-paywall' ); ?></span></h3>

                        <div class="inside">

                        <table id="lpaywall_default_restriction_options" class="form-table">

                            <tr class="restriction-options">
                                <th>
                                    <label for="restriction-post-type-' . $row_key . '"><?php _e( 'Restrictions', 'leaky-paywall' ); ?></label>
                                </th>
                                <td id="issuem-leaky-paywall-restriction-rows">

                                    <table>
                                        <tr>
                                            <th>Post Type</th>
                                            <th>Taxonomy <span style="font-weight: normal; font-size: 11px; color: #999;"> Category,tag,etc.</span></th>
                                            <th>&nbsp;</th>
                                        </tr>

                                        <?php
                                        $last_key = -1;

                                        if ( !empty( $settings['restrictions']['post_types'] ) ) {

                                            foreach( $settings['restrictions']['post_types'] as $key => $restriction ) {

                                                if ( !is_numeric( $key ) )
                                                    continue;

                                                idra_build_lpaywall_default_restriction_row( $restriction, $key );

                                                $last_key = $key;

                                            }

                                        }
                                        ?>
                                    </table>
                                </td>
                            </tr>

                            <tr class="restriction-options">
                                <th>&nbsp;</th>
                                <td style="padding-top: 0;">
                                    <script type="text/javascript" charset="utf-8">
                                        var lpaywall_restriction_row_key = <?php echo $last_key; ?>;
                                    </script>

                                    <p>
                                        <input class="button-secondary" id="add-restriction-row" class="add-new-issuem-leaky-paywall-restriction-row" type="submit" name="add_lpaywall_restriction_row" value="<?php _e( '+ Add Restricted Content', 'leaky-paywall' ); ?>" />
                                    </p>
                                    <p class="description"><?php _e( 'By default all content is allowed.', 'leaky-paywall' ); ?></p>
                                </td>
                            </tr>

                        </table>

                        </div>

                    </div>

                    <p class="submit">
                        <input class="button-primary" type="submit" name="update_lpaywall_settings" value="<?php _e( 'Save Settings', 'puzzle-ice-dragon' ) ?>" />
                    </p>

                    <?php endif; ?>

                <?php if ( $current_tab == 'integration' ) : ?>

                    <?php do_action('ice_dragon_paywall_before_general_integration'); ?>

                    <div id="modules" class="postbox">

                        <div class="handlediv" title="Click to toggle"><br /></div>

                        <h3 class="hndle"><span><?php _e( 'Integration with your service on ice-dragon.ch', 'leaky-paywall' ); ?></span></h3>

                        <div class="inside">

                            <table id="lpaywall_administrator_options" class="form-table">

                                <tr>
                                    <th><?php _e( 'Ice Dragon Secret Key', 'leaky-paywall' ); ?></th>
                                    <td>
                                        <input type="text" id="ice_dragon_secret_key" class="large-text" name="<?php echo IDRA_Constants::SETTINGS_KEY_HMAC_SECRET ?>" value="<?php echo esc_attr($settings[IDRA_Constants::SETTINGS_KEY_HMAC_SECRET]); ?>">
                                        <p class="description">
                                            <?php _e( "Keep this information private! This secret is used to verify the validity of the vouchers purchased by your visitors.", 'leaky-paywall' ); ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th><?php _e( "Dragon's Nest URL", 'leaky-paywall' ); ?></th>
                                    <td>
                                        <p><?php
                                            require_once('include/class-dragons-nest.php');
                                            $dragonsNest = new IDRA_DragonsNest();
                                            echo $dragonsNest->getFullDragonsNestURL();
                                            ?></p>
                                    </td>
                                </tr>

                                <?php wp_nonce_field( 'pitc_ice_dragon_general_options', 'pitc_ice_dragon_general_options_nonce' ); ?>

                            </table>

                        </div>

                    </div>

                    <?php do_action('ice_dragon_paywall_after_general_settings'); ?>

                    <?php do_action( 'ice_dragon_paywall_settings_form', $settings ); // here for backwards compatibility ?>

                    <p class="submit">
                        <input class="button-primary" type="submit" name="update_lpaywall_settings" value="<?php _e( 'Save Settings', 'leaky-paywall' ) ?>" />
                    </p>

                <?php endif; ?>

            </form>



        </div>

        </div>

        </div>

        </div>
        <?php

    }

    /**
     * Upgrade function, tests for upgrade version changes and performs necessary actions
     *
     * @since 1.0.0
     */
    function upgrade() {

        $settings = $this->get_settings();

        $settings['version'] = IDRA_PLUGIN_VERSION;
        $settings['db_version'] = IDRA_DB_VERSION;

        $this->update_settings( $settings );

    }

    /**
     * Process ajax calls for notice links
     *
     * @since 2.0.3
     */
    function ajax_process_notice_link() {

        $nonce = $_POST['nonce'];

        if ( ! wp_verify_nonce( $nonce, 'leaky-paywall-notice-nonce' ) )
            die ( 'Busted!');

        exit;

    }
}
