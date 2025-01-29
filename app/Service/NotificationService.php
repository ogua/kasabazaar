<?php

namespace App\Service;

class NotificationService
{
    public static function sendMailToSender($email,$message)
    {
        logger($email.''.$message);
    }

    public static function sendSmsToSender($phone,$message)
    {
        logger($phone.''.$message);
    }
}