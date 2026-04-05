<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topup extends Model {
    public function card() {
        return $this->belongsTo(VirtualCard::class, 'virtual_card_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function deposit() {
        return $this->belongsTo(Deposit::class)->withDefault([
            'gateway' => literal(...[
                'image' => 'wallet.png',
                'name'  => 'User Wallet',
            ]),
        ]);
    }
}
