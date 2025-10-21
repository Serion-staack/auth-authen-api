<?php

namespace App\Enum;

enum UserTypesEnum
{
    public const ADMIN       = 1;
    public const SUBJECT     = 2;
    public const STAFF       = 3;


    public static function userTypes(){

        return [
            self::ADMIN,
            self::SUBJECT,
            self::STAFF

            ];
    }
}
