<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdminNotification extends Model
{
    public function user()
    {
    	return $this->belongsTo(User::class);
    }

    public function isMailFailure(): bool
    {
        return Str::contains(strtolower((string) $this->title), [
            'smtp error',
            'recipient address rejected',
            'policy rejection',
            'quota exceeded',
        ]);
    }

    public function extractedRecipient(): ?string
    {
        $title = (string) $this->title;

        if (preg_match('/failed:\s*([^\s<]+@[^\s:>]+)/i', $title, $matches)) {
            return strtolower(trim($matches[1]));
        }

        if (preg_match('/<([^>]+@[^>]+)>/', $title, $matches)) {
            return strtolower(trim($matches[1]));
        }

        return null;
    }

    public function extractedFailureReason(): ?string
    {
        $title = trim((string) $this->title);

        if (preg_match('/Recipient address rejected:\s*(.+)$/i', $title, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    public function extractedHelpUrl(): ?string
    {
        $title = (string) $this->title;

        if (preg_match('/https?:\/\/[^\s]+/i', $title, $matches)) {
            return trim($matches[0]);
        }

        return null;
    }

    public function extractedFailureSummary(): ?string
    {
        $reason = $this->extractedFailureReason();

        if (!$reason) {
            return null;
        }

        if (Str::contains(strtolower($reason), 'quota exceeded')) {
            return 'DreamHost blocked the send because the outgoing email quota or sending policy was exceeded.';
        }

        return $reason;
    }
}
