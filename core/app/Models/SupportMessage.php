<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model {

    protected $casts = [
        'is_ai_response' => 'boolean',
    ];

    public function authorLabel(): Attribute {
        return new Attribute(
            get: function () {
                if ($this->is_ai_response) {
                    return 'AI Assistant';
                }

                if ((int) $this->admin_id === 0) {
                    return optional($this->ticket)->name;
                }

                return optional($this->admin)->name;
            }
        );
    }

    public function ticket() {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id', 'id');
    }

    public function admin() {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

    public function attachments() {
        return $this->hasMany(SupportAttachment::class, 'support_message_id', 'id');
    }
}
