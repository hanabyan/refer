<?php
class AUTHORIZATION
{
    public static function validateTimestamp($token,$user='admin')
    {
        $CI =& get_instance();
        $token = self::validateToken($token,$user);
        if ($token != false && (time() - $token->exp < ($CI->config->item('token_timeout') * 60))) {
            return $token;
        }
        return false;
    }
    public static function validateToken($token,$user='admin')
    {
        $CI =& get_instance();
        if ($user=='user') {
            return JWT::decode($token, $CI->config->item('jwt_key_user'));
        } else {
            return JWT::decode($token, $CI->config->item('jwt_key'));
        }
    }
    public static function generateToken($data,$user='admin')
    {
        $CI =& get_instance();
        if ($user=='user') {
            return JWT::encode($data, $CI->config->item('jwt_key_user'));
        } else {
            return JWT::encode($data, $CI->config->item('jwt_key'));
        }
    }
}