<?php
/**
 * Created by gcomte
 * Date: 10/10/19
 * Time: 2:51 PM
 */

define('COOKIE_TITLE', 'ICE_DRAGON');
define('DEFAULT_HMAC_HASHING_ALGORITHM', 'HS512');
define('JWT_LIBRARY_SUPPORTED_HASHING_ALGORITHMS', array('HS256', 'HS384', 'HS512', 'RS256', 'RS384', 'RS512'));
define('SUCCESS_MESSAGE', 'success');
define('JWT_EXPIRATION_KEY', 'exp');
define('JWT_SEGMENT_DELIMITER', '.');

define('PLUGIN_ABSOLUTE_PATH', dirname(__FILE__) . '/');
define('COMPOSER_LOADER_RELATIVE_PATH', 'vendor/autoload.php');
define('COMPOSER_LOADER_ABSOLUTE_PATH', PLUGIN_ABSOLUTE_PATH . COMPOSER_LOADER_RELATIVE_PATH);

require_once(COMPOSER_LOADER_ABSOLUTE_PATH);
use Ahc\Jwt\JWT;

class DragonsNest {

    public function registerIceDragonCookie($voucher, $paymentConfirmationSecret) {

        try {
            $payload = $this->verifyVoucher($voucher, $paymentConfirmationSecret);
            setcookie(COOKIE_TITLE, $voucher, $payload[JWT_EXPIRATION_KEY], '/');
            echo SUCCESS_MESSAGE;
            exit;

        } catch (Exception $exception) {
            echo $exception->getMessage();
            exit;
        }
    }

    public function receivedValidIceDragonCookie($paymentConfirmationSecret) {
        if(isset($_COOKIE[COOKIE_TITLE])){
            try {
                if ($this->verifyVoucher($_COOKIE[COOKIE_TITLE], $paymentConfirmationSecret)) {
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