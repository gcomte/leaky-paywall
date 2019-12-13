<?php
/**
 * Created by gcomte
 * Date: 10/10/19
 * Time: 2:51 PM
 */

define('API_NAMESPACE', 'icedragon');
define('API_VERSION', 'v1');
define('API_HTTP_METHOD', 'GET');
define('API_DRAGONSNEST_URL_PATH', 'dragonsnest');
define('API_VOUCHER_KEY', 'voucher');

define('DEFAULT_HMAC_HASHING_ALGORITHM', 'HS512');
define('JWT_LIBRARY_SUPPORTED_HASHING_ALGORITHMS', array('HS256', 'HS384', 'HS512', 'RS256', 'RS384', 'RS512'));
define('JWT_EXPIRATION_KEY', 'exp');
define('JWT_SEGMENT_DELIMITER', '.');

define('HEADER_ALLOW_ORIGIN', 'Access-Control-Allow-Origin: ' . IceDragonConstants::ICE_DRAGON_DOMAIN);
define('HEADER_ALLOW_CREDENTIALS', 'Access-Control-Allow-Credentials: true');
define('HEADER_ALLOW_METHODS', 'Access-Control-Allow-Methods: GET');
define('HEADER_EXPOSE_HEADERS', 'Access-Control-Expose-Headers: Set-Cookie');


define('PLUGIN_ABSOLUTE_PATH', dirname(__FILE__, 2) . '/');
define('COMPOSER_LOADER_RELATIVE_PATH', 'vendor/autoload.php');
define('COMPOSER_LOADER_ABSOLUTE_PATH', PLUGIN_ABSOLUTE_PATH . COMPOSER_LOADER_RELATIVE_PATH);

require_once(COMPOSER_LOADER_ABSOLUTE_PATH);
use Ahc\Jwt\JWT;

class DragonsNest {

    public function registerRestAPI() {
        add_action('rest_api_init', function () {
            register_rest_route(API_NAMESPACE . '/' . API_VERSION, '/' . API_DRAGONSNEST_URL_PATH . '/(?P<' . API_VOUCHER_KEY . '>[a-zA-Z0-9+/.=_-]+)', array(
                'methods' => API_HTTP_METHOD,
                'callback' => array($this, 'receiveVoucher'),
            ));
        });
    }

    function receiveVoucher(WP_REST_Request $data){
        $settings = get_ice_dragon_paywall_settings();
        $paymentConfirmationSecret = $settings[IceDragonConstants::SETTINGS_KEY_HMAC_SECRET];

        $this->registerIceDragonCookie($data[API_VOUCHER_KEY], $paymentConfirmationSecret);
    }

    private function registerIceDragonCookie($voucher, $paymentConfirmationSecret) {

        try {
            $payload = $this->verifyVoucher($voucher, $paymentConfirmationSecret);

            // allow CORS
            header(HEADER_ALLOW_ORIGIN);
            header(HEADER_ALLOW_CREDENTIALS);
            header(HEADER_ALLOW_METHODS);
            header(HEADER_EXPOSE_HEADERS);

            setcookie(IceDragonConstants::COOKIE_TITLE, $voucher, $payload[JWT_EXPIRATION_KEY], '/');

            echo IceDragonConstants::DRAGONS_NEST_SUCCESS_MESSSAGE;
            exit;

        } catch (Exception $exception) {
            echo $exception->getMessage();
            exit;
        }
    }

    public function receivedValidIceDragonCookie($paymentConfirmationSecret) {
        if(isset($_COOKIE[IceDragonConstants::COOKIE_TITLE])){
            try {
                if ($this->verifyVoucher($_COOKIE[IceDragonConstants::COOKIE_TITLE], $paymentConfirmationSecret)) {
                    return true;
                }
            } catch (Exception $exception) {
                return false;
            }
        }
        return false;
    }

    private function verifyVoucher($voucherJWT, $secret) {
        $hmacFunction = $this->getHmacFunctionFromJWT($voucherJWT, JWT_LIBRARY_SUPPORTED_HASHING_ALGORITHMS, DEFAULT_HMAC_HASHING_ALGORITHM);
        $jwt = new JWT($secret, $hmacFunction);

        return $jwt->decode($voucherJWT);
    }

    private function getHmacFunctionFromJWT($jwt, $supportedAlgorithms, $default) {
        $serializedHashFunctionData = substr($jwt, 0, strpos($jwt, JWT_SEGMENT_DELIMITER));
        $dataObj = json_decode(base64_decode($serializedHashFunctionData));
        $derivedAlgorithm = $dataObj->alg;

        if (in_array($derivedAlgorithm, $supportedAlgorithms)) {
            return $derivedAlgorithm;
        } else {
            return $default;
        }
    }
}