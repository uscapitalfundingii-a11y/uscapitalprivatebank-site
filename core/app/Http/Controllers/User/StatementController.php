<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Traits\BankStatement;

class StatementController extends Controller
{
    use BankStatement;

    public function __construct()
    {
        $this->user = auth()->user();
        $this->view = 'Template::user.statement';
        $this->pageTitle = 'Statement';
        $this->actionBy = 'user';
    }
}
