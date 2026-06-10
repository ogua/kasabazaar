<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'sms_driver',     'value' => 'twilio',                              'group' => 'sms'],
            ['key' => 'twilio_sid',     'value' => env('TWILIO_SID', ''),                 'group' => 'sms'],
            ['key' => 'twilio_token',   'value' => env('TWILIO_AUTH_TOKEN', ''),          'group' => 'sms'],
            ['key' => 'twilio_from',    'value' => env('TWILIO_FROM', ''),                'group' => 'sms'],
            ['key' => 'mnotify_key',    'value' => env('MNOTIFY_API_V2_KEY', ''),         'group' => 'sms'],
            ['key' => 'mnotify_sender', 'value' => env('MNOTIFY_API_SENDER_ID', 'RDDSHIP'), 'group' => 'sms'],
        ];

        foreach ($defaults as $row) {
            Setting::firstOrCreate(['key' => $row['key']], $row);
        }
    }
}
