<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSignupVerification extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $hidden = ['password', 'code_hash'];

    protected function casts(): array
    {
        return [
            'whatsapp_opt_in' => 'boolean',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function isOpen(): bool
    {
        return $this->verified_at === null && $this->expires_at->isFuture();
    }

    /**
     * The destination with all but its last few characters hidden.
     */
    public function maskedDestination(): string
    {
        if ($this->channel === 'email') {
            [$local, $domain] = array_pad(explode('@', $this->destination, 2), 2, '');

            return mb_substr($local, 0, 1).str_repeat('•', max(1, mb_strlen($local) - 1)).'@'.$domain;
        }

        $digits = preg_replace('/\D+/', '', $this->destination);

        return str_repeat('•', max(0, strlen($digits) - 4)).substr($digits, -4);
    }
}
