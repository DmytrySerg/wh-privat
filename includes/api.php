<?php
/**
 * Если файл вызван на прямую или не из WP то прекращаем работу
 **/
if ( ! function_exists( 'add_action' ) ) exit;

class Api {
    /**
     * Мерчант id
     **/
    protected $merchant;
    /**
     * Строка перевода плагина
     **/
    public static $textdomain;
    /**
     * Если будет == false, то значит где-то произошла ошибка
     **/
    public $status = true;
    /**
     * Если где-то произошла ошибка и тут сохранён текст ошибки
     **/
    public $error = false;
    /**
     * Разрешённые валюты для api
     **/
    public $currencys = array( 'UAH', 'USD', 'EUR' );
    /**
     * Разрешённые сортировки для api
     **/
    public $error_json;


    /**
     * Конструктор. Принимает массив с настройками
     **/

    public function __construct( $args = array(), $textdomain = 'wh-privat24' ) {
        self::$textdomain = $textdomain;
        if( empty( $args ) ) :
            $this->status = false;
            $this->error = __('Настройки не заданы.', self::$textdomain );
        elseif( ! isset( $args['merchant'] ) || empty( $args['merchant'] ) ) :
            $this->status = false;
            $this->error = __('Мерчант не указан или указан не верно.', self::$textdomain );
//        elseif( ! isset( $args['token'] ) || empty( $args['token'] ) || ! is_string( $args['marker'] ) ) :
//            $this->status = false;
//            $this->error = __('Токен не указан или указан не верно.', self::$textdomain );
        else :
            $this->merchant = $args['merchant'];
            $this->error_json = $args['error_json'];
        endif;
    }
    /**
     * Функция возвращает есть ли ошибка или нет
     **/
    public function status() {
        return $this->status;
    }

    /**
     * Функция возвращает текст ошибки, если она есть, или false, если нету.
     **/
    public function error() {
        return $this->error;
    }

}