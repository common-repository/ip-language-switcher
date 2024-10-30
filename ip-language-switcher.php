<?php
/**
 * Plugin Name: IP Language Switcher
 * Description: Language is no longer a barrier for developer.
 * Version: 1.0.1
 * Author: Henry Me
 * Author URI: http://henryme.com/
 */
if ( ! class_exists( 'RID_IP_Language_Switcher' ) ) :
/**
 * Main RID Class.
 *
 * @class RID_IP_Language_Switcher
 * @version	1.0.1
 */
class RID_IP_Language_Switcher {
	/**
	 * RID locale.
	 *
	 * @var string
	 */
	private $olocale = '';
	/**
	 * Hook in methods.
	 */
	public function __construct() {	
        $this->get_olocale_by_ip();
        add_filter( 'locale', array($this, 'override_locale'), 999999 );
        load_plugin_textdomain( 'ip-language-switcher', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
        add_action( 'admin_menu', array( $this, 'add_settings_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settingst' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'plugin_action_links' ) );
	}
    public static function plugin_action_links( $links ) {
        $action_links = array(
            'settings' => '<a href="' . admin_url( 'options-general.php?page=ip-language-switcher' ) . '" aria-label="' . esc_attr__( 'IP Language Switcher settings', 'ip-language-switcher' ) . '">' . esc_html__( 'Settings', 'ip-language-switcher' ) . '</a>',
        );
        return array_merge( $action_links, $links );
    }
    public function admin_scripts() {
        $plurl = untrailingslashit( plugins_url( '/', __FILE__ ) );
        wp_enqueue_script( 'rid_ipls', $plurl . '/js/main.js', array( 'jquery' ), null, true );
        wp_enqueue_style( 'rid_ipls_style', $plurl . '/css/style.css', array(), null );
    }
    public function override_locale( $locale ) {
        $locale = $this->get_olocale();
        return $locale;
    }
    public function register_settingst() {
        require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
        register_setting( 'rid_ip_lang_settings', 'rid_ip_map_lang' );
        if ( ! empty( $_POST ) && isset($_POST['rid_ip_map_lang']) ) {
            $postmaplang = $_POST['rid_ip_map_lang'];
            foreach( $postmaplang as $k => $post ) {
                if ( empty($post['ip']) ) {
                    unset($postmaplang[$k]);
                    continue;
                }
                // Handle translation install.
                if ( ! empty( $post['language'] ) && wp_can_install_language_pack() ) {  // @todo: Skip if already installed
                    wp_download_language_pack( $post['language'] );
                }
                $postmaplang[$k]['ip'] = trim($post['ip']);
            }
            $_POST['rid_ip_map_lang'] = $postmaplang;
        }
    }
    public function add_settings_menu() {
        add_options_page( esc_html__('IP Language Switcher', 'ip-language-switcher'), 'IP Language', 'manage_options', 'ip-language-switcher', array($this, 'ip_language_options') );
    }
    public function ip_language_options() {
        require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
        ?>
        <div class="wrap rid-ipls">
            <h1><?php echo esc_html__('IP Language Switcher', 'ip-language-switcher'); ?></h1>
            <?php
            $languages = get_available_languages();
            $translations = wp_get_available_translations();
            if ( ! is_multisite() && defined( 'WPLANG' ) && '' !== WPLANG && 'en_US' !== WPLANG && ! in_array( WPLANG, $languages ) ) {
                $languages[] = WPLANG;
            }
            
            if ( ! empty( $languages ) || ! empty( $translations ) ) :
                $locale = get_option( 'WPLANG' );
                if ( ! in_array( $locale, $languages ) ) {
                    $locale = '';
                }
            ?>
            <form action="options.php" method="post">
                <?php settings_fields('rid_ip_lang_settings'); ?>
                <?php do_settings_sections('rid_ip_lang_settings'); ?>
                <?php
                    if ( isset($translations[ $locale ]) ) {
                        $native_name = $translations[ $locale ]['native_name'];
                    } else {
                        $native_name = 'English (United States)';
                    }
                    printf( '<p>%s: <b>%s</b></p>', esc_html__('Default Language for all of IP', 'ip-language-switcher'), $native_name );
                    printf( '<input type="hidden" class="current_language_code" value="%s" />', $locale)
                ?>
                <?php printf( '<p>%s: <b>%s</b></p>', esc_html__('Your IP', 'ip-language-switcher'), $this->get_the_user_ip() ); ?>
                <div class="option-section">
                    <div class="header-wrapper clearfix">
                        <div class="option-heading ip-heading">IP</div>
                        <div class="option-heading language-heading"><?php echo esc_html__('Language', 'ip-language-switcher'); ?></div>
                    </div>
                    <table class="clearfix map-fields">
                        <tbody>
                            <?php
                                $this->row_ip_language( '', $locale, $languages, $translations );
                                $options = get_option( 'rid_ip_map_lang' );
                                if ( $options ) {
                                    foreach ( $options as $key => $option ) {
                                        $option['stt'] = $key;
                                        $this->row_ip_language( $option, $locale, $languages, $translations );
                                    }
                                } else {
                                    $this->row_ip_language( array(), $locale, $languages, $translations );
                                }
                            ?>
                            <tr class="pin">
                                <td colspan="4"><a class="button add-row"><?php echo esc_html__('Add IP', 'ip-language-switcher'); ?></a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php submit_button(null, 'primary', 'submit', false); ?>
            </form>
            <?php endif; ?>
        </div>
        <?php
    }
    public function row_ip_language( $option, $locale, $languages, $translations ) {
        if ( ! is_array($option) ) {
            $class = 'original-repeatable-field';
            $name = 'rid_temp';
            $selected = $locale;
            $ip = '';
            $stt = 'auto';
        } else {
            $name = 'rid_ip_map_lang';
            $class = 'repeatable-field';
            if ( isset($option['language']) ) {
                $selected = $option['language'];
                $ip = $option['ip'];
                $stt = $option['stt'];
            } else {
                $selected = $locale;
                $ip = '';
                $stt = 0;
            }
        }
        ?>
        <tr class="map-row <?php echo esc_attr($class); ?>">
            <td class="ip-col">
                <input type="text" size="40" name="<?php echo esc_attr($name); ?>[<?php echo esc_attr($stt); ?>][ip]" class="rid_ip"
                value="<?php echo esc_attr($ip); ?>" placeholder="<?php echo esc_html__('Type the IP...', 'ip-language-switcher'); ?>" autocomplete="off">
            </td>
            <td class="arrow-col">
                <span class="right-arrow">→</span>
            </td>
            <td class="language-right-col">
                <?php
                wp_dropdown_languages( array(
                    'name'         => $name.'['.$stt.'][language]',
                    'id'           => 'WPLANG',
                    'selected'     => $selected,
                    'languages'    => $languages,
                    'translations' => $translations,
                    'show_available_translations' => ( ! is_multisite() || is_super_admin() ) && wp_can_install_language_pack(),
                ) );
                ?>
            </td>
            <td class="row-action-buttons">
                <span class="remove-row" data-profile-id="0"></span>
            </td>
        </tr>
        <?php
    }
	public function get_the_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
    		$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
    		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
            $ip = $_SERVER['REMOTE_ADDR'];
		}
        if ( $ip == '::1' ) {
            $ip = '127.0.0.1';
        }
		return apply_filters( 'wpb_get_ip', $ip );
	}
	public function get_olocale_by_ip() {
		$currentLang = get_option( 'WPLANG' );
		if ( $currentLang === false ) {
			$currentLang = 'en_US';
		}
        $ips = array();
		$options = get_option( 'rid_ip_map_lang' );
        if ( $options ) {
            foreach ( $options as $option ) {
                $ips[$option['ip']] = $option['language'];
            }
        }
        $iplocal = $this->get_the_user_ip();
		if ( isset( $ips[$iplocal] ) ) {
			$this->set_olocale( $ips[$iplocal] );
		} else {
			$this->set_olocale( $currentLang );
		}
	}
	public function set_olocale( $locale ) {
		$this->olocale = $locale;
	}
	public function get_olocale() {
		return $this->olocale;
	}
}

endif;

new RID_IP_Language_Switcher();