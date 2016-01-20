<?php

namespace AppBundle\Enum;


class LoginResult
{
    const SUCCESS = 'Success';
    
    const NO_NAME = 'NoName';
    
    const ILLEGAL = 'Illegal';
    
    const NOT_EXISTS = 'NotExists';
    
    const EMPTY_PASS = 'EmptyPass';
    
    const WRONG_PASS = 'WrongPass';
    
    const WRONG_PLUGIN_PASS = 'WrongPluginPass';
    
    const CREATE_BLOCKED = 'CreateBlocked';
    
    const THROTTLED = 'Throttled';
    
    const BLOCKED = 'Blocked';
    
    const MUST_BE_POSTED = 'mustbeposted';
    
    const NEED_TOKEN = 'NeedToken';

    private static $messages = [
        self::SUCCESS           => 'Login attempt was successful',
        self::NO_NAME           => 'No username was provided',
        self::ILLEGAL           => 'An illegal username was provided',
        self::NOT_EXISTS        => 'The provided username does not exist',
        self::EMPTY_PASS        => 'No password was provided',
        self::WRONG_PASS        => 'The password is incorrect',
        self::WRONG_PLUGIN_PASS => 'The password is incorrect',
        self::CREATE_BLOCKED    => 'Wikipedia tried to automatically create a new account, but the IP address has been blocked from account creation',
        self::THROTTLED         => 'Too many login attempt in a short time',
        self::BLOCKED           => 'The user is blocked',
        self::MUST_BE_POSTED    => 'Request was not sent with POST',
        self::NEED_TOKEN        => 'A login token of sessionid is needed',
    ];

    /**
     * @param  string $result
     * @return string
     */
    public static function messageFor($result) {
        return (array_key_exists($result, self::$messages))
            ? self::$messages[$result]
            : sprintf("Unknows result '%s'", $result);
    }

}
