<?php
class Remixd_Settings
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    // Set constants for setting names/types
    const SETTING_OPTION_GROUP = 'remixd_option_group';
    const SETTING_NAME = 'remixd_settings';
    const PAGE = 'remixd-settings-admin';
    const SECTION_INTEGRATION = 'setting_section_integration';
    const SECTION_DISPLAY = 'setting_section_display';

    /**
     * Start up and run the WP settings page
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('wp_ajax_activate_player', array($this, 'activate_player'));
        add_action('wp_ajax_fetch_player_tag', array($this, 'fetch_player_tag'));
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        add_menu_page(
            'Remixd Voice Settings',
            'Remixd Voice',
            'manage_options',
            'remixd',
            array($this, 'create_admin_page'),
            REMIXD_PLUGIN_URL . '/assets/images/remixd-icon.svg'
        );
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            self::SETTING_OPTION_GROUP, // Option group
            self::SETTING_NAME, // Option name
            array($this, 'sanitize') // Sanitize
        );
        $this->addSettingsSection(
            self::SECTION_INTEGRATION, // ID
            'Integration', // Title
            'print_section_info' // Callback
        );
        $this->addSettingsField(
            'activation_key', // ID
            'Activation Key', // Title
            'activation_key_callback', // Callback
            self::SECTION_INTEGRATION // Section
        );
        $this->addSettingsField(
            'activation_status', // ID
            'Status', // Title
            'activation_status_callback', // Callback
            self::SECTION_INTEGRATION // Section
        );
        $this->addSettingsField(
            'player_version', // ID
            'Player Version', // Title
            'player_version_callback', // Callback
            self::SECTION_INTEGRATION // Section
        );
        $this->addSettingsSection(
            self::SECTION_DISPLAY, // ID
            'Display', // Title
            'print_section_display_info' // Callback
        );
        $this->addSettingsField(
            'display',
            'Blog posts player',
            'display_callback',
            self::SECTION_DISPLAY
        );
    }

    private function addSettingsSection($id, $title, $callbackMethod)
    {
        add_settings_section(
            $id, // ID
            $title, // Title
            array($this, $callbackMethod), // Callback
            self::PAGE // Page
        );
    }

    private function addSettingsField($id, $title, $callbackMethod, $section)
    {
        add_settings_field(
            $id, // ID
            $title, // Title
            array($this, $callbackMethod), // Callback
            self::PAGE, // Page
            $section // Section
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize($input)
    {
        $new_input = array();

        if (isset($input['activation_key']))
            $new_input['activation_key'] = sanitize_text_field($input['activation_key']);

        if (isset($input['activation_status']))
            $new_input['activation_status'] = intval($input['activation_status']);

        if (isset($input['display']))
            $new_input['display'] = intval($input['display']);

        if (isset($input['player_version'])) :

            if( strlen(sanitize_text_field($input['player_version'])) == 2) :
                $new_input['player_version'] = sanitize_text_field($input['player_version']);
            else :
                $new_input['player_version'] = 'v3';
            endif;

        endif;

        return $new_input;
    }

    /**
     * Print the Integration Section text
     */
    public function print_section_info()
    {
        print 'Once you have submitted your registration on Remixd and we approve your account, you will receive an email with the login information to your Dashboard.<br>When you login to your Dashboard you will be able to collect an “Activation Key”.';
    }

    /**
     * Print the Display Section text
     */
    public function print_section_display_info()
    {
        print 'When and where to display a Remixd player';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function activation_key_callback()
    {
        printf(
            '<input type="text" id="activation_key" class="regular-text code" name="remixd_settings[activation_key]" value="%s" />',
            isset($this->options['activation_key']) ? esc_attr($this->options['activation_key']) : ''
        );
        printf(
            '<button type="button" id="submit_activation_key" style="top: 1px; height: 30px; position: relative" class="button button-secondary" name="submit_activation_key">Activate</button>',
            isset($this->options['activation_key']) ? esc_attr($this->options['activation_key']) : ''
        );
    }



    /**
     * Get the settings option array and print one of its values
     */
    public function player_version_callback()
    {

        printf(
            '<input type="hidden" name="remixd_settings[player_version]" id="player_version" value="%s">',
            isset($this->options['player_version']) ? esc_attr($this->options['player_version']) : ''
        );
        printf(
            '<span class="remixd-player-version" style="line-height: 27px; margin-right: 2rem;">%s</span>',
            isset($this->options['player_version']) ? esc_attr($this->options['player_version']) : 'Inactive'
        );

        printf(
            '<button type="button" id="fetch_player_tag" style="position: relative" class="button button-secondary" name="fetch_player_tag">Update</button>',
            isset($this->options['activation_key']) ? esc_attr($this->options['activation_key']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function activation_status_callback()
    {
        if (isset($this->options['activation_status']) && $this->options['activation_status'] == 1) :
            printf('<div class="remixd-activation-status"><span class="remixd-inactive hidden" style="color: #ff5500">Inactive</span> <span style="color: #55bb00" class="remixd-active">Active</span></div>');
        else :
            printf('<div class="remixd-activation-status"><span class="remixd-inactive" style="color: #ff5500">Inactive</span> <span style="color: #55bb00" class="hidden remixd-active">Active</span></div>');
        endif ;
        printf(
            '<input type="hidden" id="activation_status" name="remixd_settings[activation_status]" value="%s" />',
            isset($this->options['activation_status']) ? esc_attr($this->options['activation_status']) : ''
        );
    }

    /**
     * Display the select with display options
     */
    public function display_callback()
    {
        ?>
        <select id="display" name="remixd_settings[display]">
            <option value="1" <?php echo isset($this->options['display']) && $this->options['display'] == 1 ? esc_attr("selected") : ""; ?>>Always display</option>
            <option value="2" <?php echo isset($this->options['display']) && $this->options['display'] == 2 ? esc_attr("selected") : ""; ?>>Adjust manually</option>
            <option value="3" <?php echo isset($this->options['display']) && $this->options['display'] == 3 ? esc_attr("selected") : ""; ?>>Using Shortcode</option>
        </select>
        <?php
    }


    /**
     * Get the activation key via Ajax and send to Remixd activation route
     */

    function activate_player()
    {
        $activation_key = sanitize_text_field($_GET['activationKey']);

        $response = wp_remote_post( REMIXD_ROUTE_ACTIVATE_PLAYER . $activation_key);

        if(!isset( $_GET['activationKey']) || $_GET['activationKey'] == '') :
            header( "Content-Type: application/json", true, 400);
            echo wp_json_encode(['message' => esc_html('Missing activation key. Try again!')]);

            exit();
        endif;

        if ( is_array( $response ) && ! is_wp_error( $response ) ) :
            $headers = $response['headers'];
            $body    = $response['body'];
            $control = $response['response'];
        endif;

        if ($control['code'] != 200) :
            header( "Content-Type: application/json", true, $control['code']);
            echo wp_json_encode(['message' => esc_html($control['message'])]);
        else :
            header( "Content-Type: application/json" );
            echo wp_json_encode(['message' => esc_html('Activated')]);
        endif;

        exit();
    }


    /**
     * Get the activation key via Ajax and send to Remixd activation route
     */

    function fetch_player_tag()
    {

        if(!isset( $_GET['activationKey']) || $_GET['activationKey'] == '' ) :
            header( "Content-Type: application/json", true, 400);
            echo wp_json_encode(['message' => esc_html('Missing activation key. Try again!')]);

            exit();
        endif;

        $activation_key = sanitize_text_field($_GET['activationKey']);

        $response = wp_remote_get(REMIXD_ROUTE_GET_TAG . $activation_key);

        if ( is_array( $response ) && ! is_wp_error( $response ) ) :
            $headers = $response['headers'];
            $control = $response['response'];
        endif;

        $tag = wp_remote_retrieve_body( $response );

        if ($control['code'] != 200) :
            header( "Content-Type: application/json", true, $control['code']);
            echo wp_json_encode(['message' => esc_html($control['message'])]);
        else :
            header( "Content-Type: application/json" );
            echo wp_json_encode(['tag' => $tag, 'version' => $this->get_player_version(esc_html($tag))]);
        endif;

        exit();
    }

    /**
     * Options page callback
     */

    public function get_player_version($tag)
    {
        if (strpos($tag, 'player/v3/') !== false)
            return 'v3';

        if (strpos($tag, 'player/v4/') !== false)
            return 'v4';

        return 'v5';
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option('remixd_settings');
        ?>
        <div class="wrap">
            <h1>Remixd Settings</h1>
            <hr>
            <form method="post" action="options.php">
                <?php
                settings_fields('remixd_option_group');
                do_settings_sections('remixd-settings-admin');
                ?>
                <p>Remixd Player Shortcode: <code>[remixd_player]</code></p>
                <?php
                submit_button();
                ?>
            </form>
            <script>
                jQuery('#submit_activation_key').click(function() {
                    jQuery(this).prop('disabled', true).text('Please wait...');
                    let activationKey = jQuery('#activation_key').val();
                    jQuery.ajax({
                        type: "GET",
                        url: ajaxurl,
                        data: { action: 'activate_player' , activationKey: activationKey }
                    }).done(function( msg ) {
                        jQuery('#submit_activation_key').prop('disabled', false).text('✓ Activated');
                        jQuery('.remixd-activation-status .remixd-inactive').addClass('hidden');
                        jQuery('.remixd-activation-status .remixd-active').removeClass('hidden');
                        jQuery('#activation_status').val('1');
                        jQuery('#fetch_player_tag').trigger('click');
                    }).fail(function (jqXHR, textStatus, error) {
                        console.log(jqXHR);
                        jQuery('#submit_activation_key').prop('disabled', false).text(jqXHR.responseJSON.message);
                        jQuery('.remixd-activation-status .remixd-active').addClass('hidden');
                        jQuery('.remixd-activation-status .remixd-inactive').removeClass('hidden');
                        jQuery('#activation_status').val('');
                    });
                });

                jQuery('#fetch_player_tag').click(function() {
                    jQuery(this).prop('disabled', true).text('Fetching...');
                    let activationKey = jQuery('#activation_key').val();
                    jQuery.ajax({
                        type: "GET",
                        url: ajaxurl,
                        data: { action: 'fetch_player_tag' , activationKey: activationKey }
                    }).done(function( msg ) {
                        jQuery('#fetch_player_tag').prop('disabled', false).text('✓ Updated. Proceed with Save Changes');
                        jQuery('#player_version').val(msg.version);
                        jQuery('.remixd-player-version').text(msg.version);

                    }).fail(function (jqXHR, textStatus, error) {
                        jQuery('#fetch_player_tag').prop('disabled', false).text(jqXHR.responseJSON.message);
                    });
                });

            </script>
        </div>
        <?php
    }

}
