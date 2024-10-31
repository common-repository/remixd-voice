<?php
class Remixd_Player
{
    private $options;


    /**
     * Start up
     */
    public function __construct()
    {
        $this->options = get_option('remixd_settings');
        add_action('wp_enqueue_scripts', array($this, 'enqueue_remixd_audio_player_script'));
        add_action('save_post', array($this, 'save_remixd_meta'));
        add_action('add_meta_boxes', array($this, 'add_remixd_meta_box'));
        add_shortcode('remixd_player', array($this, 'add_remixd_shortcode'));
        $this->add_remixd_player();
    }

    private function is_active()
    {
        return isset($this->options['activation_status']) && $this->options['activation_status'] == 1;
    }

    public function enqueue_remixd_audio_player_script() {
        $handle = 'remixd-audio-player-script';
        $player_version = get_option('player_version');

        wp_enqueue_script(
            $handle,
            'https://player.remixd.com/player/index.js',
            array(),
            $player_version,
            true
        );
    }


    /*
    * Printing the player script tag depending on the settings display option
    */
    public function player_tag() {
        if (!$this->is_active()) {
            return;
        }

        if (is_single() && 'post' == get_post_type() ) :

            $allowed_tags = array(
                'script' => array(
                    'src' => true,
                    'type' => true,
                    'id' => true,
                    'charset' => true,
                    'async' => true
                )
            );

            if ($this->options['display'] == 1) :
                echo wp_kses( $this->get_player_tag(), $allowed_tags );
                return;
            endif;

            global $post;
            $remixd_voice = get_post_meta($post->ID, 'remixd_voice', true);

            echo isset($remixd_voice) && $remixd_voice == 'on' ? wp_kses( $this->get_player_tag(), $allowed_tags ) : '';

        endif;
    }


    /*
    * Place the player script tag at the beginning of the blog article (before the first paragraph)
    */
    public function add_content_start_player_tag($content) {
        // Check if the feature is active
        if (!$this->is_active()) {
            return $content;
        }

        // Enqueue the main stylesheet and add custom inline CSS
        wp_enqueue_style('main-styles', get_stylesheet_uri());
        $inline_css = "
    .wp-remixd-voice-wrapper {
        /* Your custom styles here */
        margin-bottom: 20px;
        text-align: center;
    }
    ";
        wp_add_inline_style('main-styles', $inline_css);

        // Check if it is a single post
        if (is_single() && 'post' == get_post_type()) {
            $player_tag = '<div class="wp-remixd-voice-wrapper">' . $this->get_player_tag() . '</div>';

            // AMP endpoint handling
            if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
                global $post;
                $player_amp_tag = '<div class="wp-remixd-voice-wrapper"></div>';
                wp_add_inline_script('remixd-audio-player-script', str_replace("STANDARD_PAGE_PERMALINK", esc_url(get_permalink()), '<style amp-custom>.remixdad{width:100%; max-width: 100%;}</style><amp-ad type="remixd" height="100" data-version="5" class="remixdad" data-url="STANDARD_PAGE_PERMALINK"></amp-ad>'));

                // Display options handling
                if ($this->options['display'] == 1) {
                    return $player_amp_tag . $content;
                }

                if ($this->options['display'] == 2) {
                    $remixd_voice = get_post_meta($post->ID, 'remixd_voice', true);
                    if (isset($remixd_voice) && $remixd_voice == 'on') {
                        return $player_amp_tag . $content;
                    }
                }

            } else {
                // Non-AMP versions
                if ($this->options['display'] == 1) {
                    return $player_tag . $content;
                }

                if ($this->options['display'] == 2) {
                    global $post;
                    $remixd_voice = get_post_meta($post->ID, 'remixd_voice', true);
                    if (isset($remixd_voice) && $remixd_voice == 'on') {
                        return $player_tag . $content;
                    }
                }

            }
            return $content;
        }

        return $content;
    }


    /*
    * Place the player script tag depending on the player version (standard goes to the content start, sticky to the footer, and left-floated to the footer)
    */
    public function add_remixd_player()
    {
        if(!empty($this->options)) {
            $this->options['player_version'] == 'v5'
                ? add_filter('the_content', array($this, 'add_content_start_player_tag'), 9999 )
                : add_action('wp_footer', array($this, 'player_tag'));
        }
    }


    /*
    * Add meta box with option to turn on/off the player for the certain blog article
    */
    public function add_remixd_meta_box()
    {
        if(!empty($this->options)) {
            if ($this->options['display'] == 2) {
                add_meta_box(
                    'remixd_meta_box',
                    'Remixd Voice',
                    array($this, 'show_remixd_fields_meta_box'),
                    'post',
                    'side',
                    'high'
                );
            }
        }
    }


    /*
    * Add an input checkbox control
    */
    public function show_remixd_fields_meta_box()
    {
        global $post;

        $remixd_voice = get_post_meta($post->ID, 'remixd_voice', true);

        ?>
        <input type="hidden" name="remixd_meta_box_nonce" value="<?php echo esc_attr( wp_create_nonce(basename(__FILE__)) ); ?>">
        <p>
            <label for="remixd_voice">
                <input type="checkbox" id="remixd_voice" name="remixd_voice" value="on"
                    <?php echo isset($remixd_voice) && $remixd_voice == 'on' ?  esc_attr('checked') : "";  ?> > Player
            </label></p>
        <?php
    }


    /*
    * Add an input checkbox control
    */
    public function save_remixd_meta($post_id)
    {
        // verify nonce
        if(isset($_POST['remixd_meta_box_nonce'])) {
            if (!wp_verify_nonce($_POST['remixd_meta_box_nonce'], basename(__FILE__))) {
                return $post_id;
            }
            // check autosave
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return $post_id;
            }
            // check permissions
            if (isset( $_POST['post_type']) ) {
                if ('page' === $_POST['post_type']) {
                    if (!current_user_can('edit_page', $post_id)) {
                        return $post_id;
                    } elseif (!current_user_can('edit_post', $post_id)) {
                        return $post_id;
                    }
                }
            }
            $remixd_meta_data = '';

            if (isset( $_POST['remixd_voice']) ) {
                $remixd_meta_data = sanitize_text_field( $_POST['remixd_voice'] );
            }

            if ( $remixd_meta_data == 'on') {
                update_post_meta($post_id, 'remixd_voice', $remixd_meta_data);
            } else {
                delete_post_meta($post_id, 'remixd_voice');
            }
        }
    }

    /*
    * Enqueue the new script where the tag was used to inject the player
    */
    public function get_player_tag() {
        wp_enqueue_script('remixd-audio-player-script');
        return '<script async charset="utf-8" id="remixd-audio-player-script" type="text/javascript"></script>';
    }


    /*
    * Add the player script tag using shortcode [remixd_player]
    */
    public function add_remixd_shortcode()
    {
        if (!$this->is_active()) {
            return '';
        }

        $handle = 'remixd-styles';
        wp_enqueue_style($handle, false);

        if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {

            $inline_css = '.remixdad{width:100%; max-width: 100%;}';
            wp_add_inline_style($handle, $inline_css);

            $shortcode = '<div class="wp-remixd-voice-wrapper">' .
                str_replace(
                    "STANDARD_PAGE_PERMALINK",
                    esc_url(get_permalink()),
                    '<amp-ad type="remixd" height="100" data-version="5" class="remixdad" data-url="STANDARD_PAGE_PERMALINK"></amp-ad>'
                ) .
                '</div>';
        } else {
            // Non-AMP content
            $shortcode = '<div class="wp-remixd-voice-wrapper">' . $this->get_player_tag() . '</div>';
        }

        return $shortcode;
    }
}