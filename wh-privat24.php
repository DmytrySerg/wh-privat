<?php
/*
Plugin Name: WH-Privat24
Description: Плагин платежей с помощью privat24.
Version: 1.0
*/

/**
 * Если файл вызван на прямую то прекращаем работу
 **/
if ( ! function_exists( 'add_action' ) ) exit;

if( ! class_exists( 'WH_Privat24' ) ) {
    final class WH_Privat24 {
        /**
         * Данный файл
         **/
        public static $file = __FILE__;
        /**
         * slug названия плагина
         **/
        public static $plugin_slug;
        /**
         * Строка перевода плагина
         **/
        public static $textdomain;
        /**
         * Путь к папке
         **/
        public static $path;
        /**
         * Урл к папке
         **/
        public static $url;
        /**
         * Ключь для записи версии плагина
         **/
        public static $option_version;
        /**
         * Ключь для записи настроек плагина
         **/
        public static $option_name;
        /**
         * Версия плагина
         **/
        public static $version;

        public static $api;

        public $data = array();

        public function __construct() {
            self::init();
            self::load_api();
            /**
             * Экшены
             **/
            add_action( 'plugins_loaded',               array( &$this, 'action_plugins_loaded'          ) );
            if ( is_admin() ) : // Экшены которые работают только в админке
                if( self::$api->status() == false ) {
                    add_action('admin_notices',         array( &$this, 'action_admin_notices'           ) );
                }
                add_action( 'admin_menu',               array( &$this, 'action_admin_menu'              ) );
                add_action( 'admin_init',               array( &$this, 'action_admin_init'              ) );
                add_action( 'admin_enqueue_scripts',    array( &$this, 'action_admin_enqueue_scripts'   ) );

            else : // Экшены работающие вне админки
                add_action( 'init',                     array( &$this, 'action_init'                    ) );

            endif;


        }
        /**
         * Функция которая заполняет переменные
         **/
        private static function init() {
            self::init_path( self::$file );
            self::$plugin_slug      =   preg_replace( '/[^\da-zA-Z]/i', '_',  basename( self::$file, '.php' ) );
            self::$textdomain       =   str_replace( '_', '-', self::$plugin_slug );
            self::$option_version   =   self::$plugin_slug . '_version';
            self::$option_name      =   self::$plugin_slug . '_options';
        }

        private static function load_api(){
            $options = get_option( self::$option_name );
            require_once self::$path . '/includes/api.php';
            self::$api = new Api( $options['settings'], self::$textdomain );
        }

        /**
         * Функция которая создаёт ajax для сохранения настроек плагина
         **/
        public function action_admin_init() {
            if( ! current_user_can( 'manage_options' ) )
                return false;
            add_action( 'wp_ajax_' . self::$plugin_slug,    array( &$this, 'ajax_do_options_save'   ) );
        }

        /**
         * Ajax функция которая сохраняет настройки
         **/
        public function ajax_do_options_save() {
            if ( empty( $_POST ) || ! isset( $_POST['options'] ) || ! check_admin_referer( self::$plugin_slug . '_action', self::$plugin_slug . '_nounce' ) )
                wp_die( __( 'Валидация не пройдена', self::$textdomain ) );

            $doing_ajax = ( (int) $_POST['doing_ajax'] == 0 ) ? false : true;


            $new_options = array();

            foreach($_POST['options'] as $key => $val ){
                $new_options[$key] = $val;
            }
            $this->deleteCache();

            $status = 'updated';
            if( ! $doing_ajax )
                wp_redirect( $_POST['_wp_http_referer'] . '&updated=' . $status );

            die($status);
        }

        public function deleteCache(){
            global $wpdb;
            $result = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM `".$wpdb->options."` WHERE option_name LIKE %s","_transient%tableshortcode%"));
            if(!empty($result)){
                $wpdb->query("DELETE FROM `".$wpdb->options."` WHERE `option_name` LIKE ('_transient%tableshortcode%')");
            }
        }

        /**
         * Подгружаем стили и скрипты к странице настроек и метабоксам
         **/
        public function action_admin_enqueue_scripts() {
            global $post;
            $screens = array( 'post', 'page' );
            $screens = apply_filters('os_av_table_post_types', $screens );
            $options = get_option( self::$option_name );
            $pp = get_current_screen();

            if( isset( $post->post_type ) && in_array( $post->post_type, $screens) ): // Стили и скрипты для метабоксов

                //Модальне окно
                wp_enqueue_style( self::$plugin_slug . '-modal-style',
                    self::$url . '/css/const_style.css',
                    array(),
                    self::$version
                );

                wp_enqueue_script( self::$plugin_slug . '-const-modal',
                    self::$url . '/js/const_modal.js',
                    array( 'jquery' ),
                    self::$version
                );
            elseif($pp->base == 'plugins'):
                ?>
                <script type="text/javascript">
                    var os_av_table = {
                        'plug_href_mes': '<?php _e('мы в twitter', self::$textdomain ); ?>'
                        // 'plug_href_kpd': '<?php _e('kpdmedia', self::$textdomain ); ?>'
                    };
                </script>
                <?php
                wp_enqueue_script( self::$plugin_slug . '-plugin-page',
                    self::$url . '/js/os_av-tables-plugin-page.js',
                    array( 'jquery' ),
                    self::$version
                );

            endif;
        }

        /**
         * Получаем путь и url к данному файлу
         **/
        public static function init_path( $path = __FILE__, $url = array() ) {
            $path               =   dirname( $path );
            $path               =   str_replace( '\\', '/', $path );
            $explode_path       =   explode( '/', $path );

            $current_dir        =   $explode_path[count( $explode_path ) - 1];
            array_push( $url, $current_dir );

            if( $current_dir == 'wp-content' ) {
                $path           =   '';
                $directories    =   array_reverse( $url );
                foreach( $directories as $dir ) {
                    $path       =   $path . '/' . $dir;
                }
                self::$path     =   str_replace( '//', '/', ABSPATH . $path);
                self::$url      =   get_bloginfo('url') . $path;
            } else {
                return self::init_path( $path, $url );
            }
        }

        /**
         * Добавляем уведомление, если api вернул ошибку
         **/
        public function action_admin_notices() {
            if( ! current_user_can( 'manage_options' ) )
                return false;
            ?>
            <div class="error">
                <p><strong><?php _e('Плагин WH-Privat24 вернул ошибку', self::$textdomain ); ?>:</strong> <i>"<?php echo self::$api->error(); ?>"</i><br />
                    <span><?php printf( __('Перейдите на <a href="%s">страницу</a> плагина и отредактируйте настройки.', self::$textdomain), admin_url('admin.php?page=' . self::$textdomain ) ); ?></span></p>
            </div>
        <?php
        }

        /**
         * Создаём страницу настроек плагина
         **/
        public function action_admin_menu() {
            if( ! current_user_can( 'manage_options' ) )
                return false;
            add_menu_page(
                _x('WH-Privat24',  'add_menu_page page title' , self::$textdomain ),
                _x('WH-Privat24',     'add_menu_page menu title' , self::$textdomain ),
                'manage_options',
                self::$textdomain,
                array( &$this,  'options_page' ),
                self::$url .    '/assets/img/png_privat24-16x16.png'
            );
        }

        /**
         * Функция отображающая страницу с настройками
         **/
        public function options_page() {
            if( ! current_user_can( 'manage_options' ) )
                wp_die( __( 'У вас недостаточно полномочий для доступа к этой странице.', self::$textdomain ) );
            include_once ( self::$path . '/includes/options.php' );
        }

        /**
         * Функция поддключающая перевод к нашему плагину от куда бы он небыл подключен
         **/
        public function load_plugin_textdomain( $domain, $plugin_abs_path = false ) {
            $locale     =   apply_filters( 'plugin_locale', get_locale(), $domain );

            if ( false !== $plugin_abs_path ) {
                $path   =   trim( $plugin_abs_path, '/' );
            } else {
                $path   =   trim( self::$path, '/' );
            }

            $mofile     =   $path . '/'. $domain . '-' . $locale . '.mo';
            return load_textdomain( $domain, $mofile );
        }

        /**
         * Подключаем перевод плагина
         **/
        public function action_plugins_loaded() {


            // Используем свою функцию т.к. то что предоставляет ВП не работает, если делать require этого плагина
            $this->load_plugin_textdomain( self::$textdomain, self::$path . '/languages' );
        }
    }
}

if( class_exists( 'WH_Privat24' ) ) {
    $OS_AV_Tables = new WH_Privat24();
//    add_action('widgets_init', create_function('', 'return register_widget("Widget_WH_Privat24");'));

    /**
     * Добавляем базовые хуки при активации, деактивации и удалении плагина
     **/
    register_activation_hook( __FILE__,     array( 'WH_Privat24',  'plugin_activation' ) );
    register_deactivation_hook( __FILE__,   array( 'WH_Privat24',  'plugin_deactivation' ) );
    register_uninstall_hook( __FILE__,      array( 'WH_Privat24',  'plugin_uninstall' ) );
    // add_action('cron_xmlparser','get_xmlparse');



} // END if(class_exists('OS_AV_Tables'))