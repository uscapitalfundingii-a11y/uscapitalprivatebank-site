<?php

namespace App\Http\Controllers\BranchStaff;

use App\Http\Controllers\Controller;
use App\Traits\BankStatement;

class StatementController extends Controller
{
    use BankStatement;

    public function __construct()
    {
        $this->view = 'branch_staff.user.statement';
        $this->pageTitle = 'Statement';
        $this->actionBy = 'branch_staff';
    }
}
