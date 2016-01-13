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
}
