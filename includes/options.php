<?php $options = get_option( self::$option_name ); ?>
<?php
if( isset( $_GET['updated'] ) ) {
    $text = __('<strong>Произошла ошибка</strong>. Настройки не обновлены.', self::$textdomain );
    if( $_GET['updated'] == 'updated' ) $text = __('Настройки успешно обновлены.', self::$textdomain); ?>
    <div id="message" class="<?php echo $_GET['updated']; ?>"><p><?php echo $text; ?></p></div>
<?php } ?>

<h1 class="page-title"><?php _e( 'WH-Privat24', self::$textdomain ); ?></h1>

<form action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST">
    <?php wp_nonce_field( self::$plugin_slug . '_action', self::$plugin_slug . '_nounce' ); ?>
    <input type="hidden" name="action" id="action" value="<?php echo self::$plugin_slug; ?>" />
    <input type="hidden" name="doing_ajax" id="doing_ajax" value="0" />

    <table>
        <tr>
            <td><label for="options_settings_merchant"><?php _e('Мерчант ID', self::$textdomain ); ?>:</label></td>
            <td>
                <input type="text" name="options[settings][merchant]" id="options_settings_merchant" value="<?php echo $options['settings']['merchant']; ?>" />
                        <span data-help="<?php _e( 'Замечание', self::$textdomain ); ?>">
                            <span><?php _e('Ваш мерчант id в Privat 24.', self::$textdomain);?></span>
                        </span>
            </td>
        </tr>
        <tr>
            <td><label for="options_settings_currency">
                    <em><?php _e('Валюта', self::$textdomain ); ?></em><br />
                </label>
            </td>
            <td>

                <select name="options[settings][currency]" id="options_settings_currency" >
                    <option <?php if( (int) $options['settings']['currency'] == 1) echo 'selected="selected"'; ?> value="1">Гривна</option>
                    <option <?php if( (int) $options['settings']['currency'] == 2) echo 'selected="selected"'; ?> value="2">Доллар</option>
                    <option <?php if( (int) $options['settings']['currency'] == 3) echo 'selected="selected"'; ?> value="3">Евро</option>
                </select>
            </td>
        </tr>
    </table>
    <div id="submit-wrapper">
        <input type="submit" class="button-primary" value="<?php _e('Сохранить', self::$textdomain ); ?>" />
    </div>
</form>