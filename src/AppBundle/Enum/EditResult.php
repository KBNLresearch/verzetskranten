<?php

namespace AppBundle\Enum;


class EditResult
{
    const SUCCESS = 'Success';
    
    const NO_TITLE = 'notitle';
    
    const NO_TEXT = 'notext';
    
    const NO_TOKEN = 'notoken';
    
    const INVALID_SECTION = 'invalidsection';
    
    const PROTECTED_TITLE = 'protectedtitle';
    
    const CANT_CREATE = 'cantcreate';
    
    const CANT_CREATE_ANON = 'cantcreate-anon';
    
    const ARTICLE_EXISTS = 'articleexists';
    
    const NO_IMAGE_REDIRECT = 'noimageredirect';

    const NO_IMAGE_REDIRECT_ANON = 'noimageredirect-anon';
    
    const SPAM_DETECTED = 'spamdetected';
    
    const FILTERED = 'filtered';
    
    const CONTENT_TOO_BIG = 'contenttoobig';
    
    const NO_EDIT = 'noedit';

    const NO_EDIT_ANON = 'noedit-anon';
    
    const PAGE_DELETED = 'pagedeleted';
    
    const EMPTY_PAGE = 'emptypage';
    
    const EMPTY_NEW_SECTION = 'emptynewsection';
    
    const EDIT_CONFLICT = 'editconflict';
    
    const REV_WRONG_PAGE = 'revwrongpage';
    
    const UNDO_FAILURE = 'undofailure';
    
    const MISSING_TITLE = 'missingtitle';
    
    const MUST_BE_POSTED = 'mustbeposted';
    
    const READ_API_DENIED = 'readapidenied';
    
    const WRITE_API_DENIED = 'writeapidenied';
    
    const NO_API_WRITE = 'noapiwrite';
    
    const BAD_TOKEN = 'badtoken';
    
    const MISSING_PARAM = 'missingparam';
    
    const INVALID_PARAM_MIX = 'invalidparammix';
    
    const INVALID_TITLE = 'invalidtitle';
    
    const NO_SUCH_PAGE_ID = 'nosuchpageid';
    
    const PAGE_CANNOT_EXIST = 'pagecannotexist';
    
    const NO_SUCH_REV_ID = 'nosuchrevid';
    
    const BAD_MD5 = 'badmd5';
    
    const HOOK_ABORTED = 'hookaborted';
    
    const PARSE_ERROR = 'parseerror';
    
    const SUMMARY_REQUIRED = 'summaryrequired';
    
    const BLOCKED = 'blocked';
    
    const RATE_LIMITED = 'ratelimited';
    
    const UNKNOWN_ERROR = 'unknownerror';
    
    const NO_SUCH_SECTION = 'nosuchsection';
    
    const SECTION_NOT_SUPPORTED = 'sectionsnotsupported';
    
    const EDIT_NOT_SUPPORTED = 'editnotsupported';
    
    const APPEND_NOT_SUPPORTED = 'appendnotsupported';
    
    const REDIRECT_APPEND_ONLY = 'redirect-appendonly';
    
    const BAD_FORMAT = 'badformat';
    
    const CUSTOM_CSS_PROTECTED = 'customcssprotected';
    
    const CUSTOM_JS_PROTECTED = 'customjsprotected';
    
    const TAGGING_NOT_ALLOWED = 'taggingnotallowed';
}
