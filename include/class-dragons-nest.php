<?php
/**
 * Created by gcomte
 * Date: 10/10/19
 * Time: 2:51 PM
 */

define('IDRA_API_NAMESPACE', 'icedragon');
define('IDRA_API_VERSION', 'v1');
define('IDRA_API_HTTP_METHOD', 'GET');
define('IDRA_API_DRAGONSNEST_URL_PATH', 'dragonsnest');
define('IDRA_API_VOUCHER_KEY', 'voucher');

define('IDRA_DEFAULT_HMAC_HASHING_ALGORITHM', 'HS512');
define('IDRA_JWT_LIBRARY_SUPPORTED_HASHING_ALGORITHMS', array('HS256', 'HS384', 'HS512', 'RS256', 'RS384', 'RS512'));
define('IDRA_JWT_EXPIRATION_KEY', 'exp');
define('IDRA_JWT_SEGMENT_DELIMITER', '.');

define('IDRA_HEADER_ALLOW_ORIGIN', 'Access-Control-Allow-Origin: ' . IDRA_Constants::ICE_DRAGON_DOMAIN);
define('IDRA_HEADER_ALLOW_CREDENTIALS', 'Access-Control-Allow-Credentials: true');
define('IDRA_HEADER_ALLOW_METHODS', 'Access-Control-Allow-Methods: GET');
define('IDRA_HEADER_EXPOSE_HEADERS', 'Access-Control-Expose-Headers: Set-Cookie');


define('IDRA_PLUGIN_ABSOLUTE_PATH', dirname(__FILE__, 2) . '/');
define('IDRA_COMPOSER_LOADER_RELATIVE_PATH', 'vendor/autoload.php');
define('IDRA_COMPOSER_LOADER_ABSOLUTE_PATH', IDRA_PLUGIN_ABSOLUTE_PATH . IDRA_COMPOSER_LOADER_RELATIVE_PATH);

require_once(IDRA_COMPOSER_LOADER_ABSOLUTE_PATH);
use Ahc\Jwt\JWT;

class IDRA_DragonsNest {

    public function registerRestAPI() {
        add_action('rest_api_init', function () {
            register_rest_route(IDRA_API_NAMESPACE . '/' . IDRA_API_VERSION, '/' . IDRA_API_DRAGONSNEST_URL_PATH . '/(?P<' . IDRA_API_VOUCHER_KEY . '>[a-zA-Z0-9+/.=_-]+)', array(
                'methods' => IDRA_API_HTTP_METHOD,
                'callback' => array($this, 'receiveVoucher'),
            ));
        });
    }

    function receiveVoucher(WP_REST_Request $data){
        $settings = idra_get_ice_dragon_paywall_settings();
        $paymentConfirmationSecret = $settings[IDRA_Constants::SETTINGS_KEY_HMAC_SECRET];

        $this->registerIceDragonCookie($data[IDRA_API_VOUCHER_KEY], $paymentConfirmationSecret);
    }

    private function registerIceDragonCookie($voucher, $paymentConfirmationSecret) {

        try {
            $payload = $this->verifyVoucher($voucher, $paymentConfirmationSecret);

            // allow CORS
            header(IDRA_HEADER_ALLOW_ORIGIN);
            header(IDRA_HEADER_ALLOW_CREDENTIALS);
            header(IDRA_HEADER_ALLOW_METHODS);
            header(IDRA_HEADER_EXPOSE_HEADERS);

            setcookie(IDRA_Constants::COOKIE_TITLE, $voucher, $payload[IDRA_JWT_EXPIRATION_KEY], '/');

            echo IDRA_Constants::DRAGONS_NEST_SUCCESS_MESSSAGE;
            exit;

        } catch (Exception $exception) {
            echo $exception->getMessage();
            exit;
        }
    }

    public function receivedValidIceDragonCookie($paymentConfirmationSecret) {
        if(isset($_COOKIE[IDRA_Constants::COOKIE_TITLE])){
            try {
                if ($this->verifyVoucher($_COOKIE[IDRA_Constants::COOKIE_TITLE], $paymentConfirmationSecret)) {
                    return true;
                }
            } catch (Exception $exception) {
                return false;
            }
        }
        return false;
    }

    private function verifyVoucher($voucherJWT, $secret) {
        $hmacFunction = $this->getHmacFunctionFromJWT($voucherJWT, IDRA_JWT_LIBRARY_SUPPORTED_HASHING_ALGORITHMS, IDRA_DEFAULT_HMAC_HASHING_ALGORITHM);
        $jwt = new JWT($secret, $hmacFunction);

        return $jwt->decode($voucherJWT);
    }

    private function getHmacFunctionFromJWT($jwt, $supportedAlgorithms, $default) {
        $serializedHashFunctionData = substr($jwt, 0, strpos($jwt, IDRA_JWT_SEGMENT_DELIMITER));
        $dataObj = json_decode(base64_decode($serializedHashFunctionData));
        $derivedAlgorithm = $dataObj->alg;

        if (in_array($derivedAlgorithm, $supportedAlgorithms)) {
            return $derivedAlgorithm;
        } else {
            return $default;
        }
    }

    public function getFullDragonsNestURL(){
        return get_home_url() . '/?rest_route=/' . IDRA_API_NAMESPACE . '/' . IDRA_API_VERSION . '/' . IDRA_API_DRAGONSNEST_URL_PATH  . '/';
    }

}