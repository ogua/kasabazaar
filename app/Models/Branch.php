<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Branch extends Model
{
    use HasUuids;

    protected $guarded = ["id"];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function quotations() : HasMany {
        return $this->hasMany(Quotation::class);
    }

    public function messageTemplates(){
        return $this->hasMany(MessageTemplate::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function payroll(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }

    // public function notifications(): HasMany
    // {
    //     return $this->hasMany(Notification::class);
    // }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
