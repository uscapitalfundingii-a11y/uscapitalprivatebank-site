<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Lib\FileManager;
use App\Lib\FormProcessor;
use App\Models\Beneficiary;
use App\Models\GeneralSetting;
use App\Models\OtherBank;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BeneficiaryController extends Controller {

    public function ownBeneficiary() {
        $beneficiaries  = Beneficiary::ownBank()->where('user_id', auth()->id())->apiQuery();
        $general        = GeneralSetting::first();
        $transferCharge = $general->transferCharge();

        $notify[]       = 'Own Beneficiary';
        return responseSuccess('own_beneficiary', $notify, [
            'beneficiaries'   => $beneficiaries,
            'transfer_charge' => $transferCharge,
            'general'         => $general,
        ]);
    }

    public function otherBeneficiary() {
        $otherBanks    = OtherBank::active()->with('form')->get();
        $beneficiaries = Beneficiary::otherBank()->where('user_id', auth()->id())->with('beneficiaryOf')->apiQuery();
        $path          = getFilePath('verify');

        $notify[]      = 'Other Beneficiary';
        return responseSuccess('other_beneficiary', $notify, [
            'beneficiaries' => $beneficiaries,
            'banks'         => $otherBanks,
            'path'          => $path,
        ]);
    }

    public function addOwnBeneficiary(Request $request, $id = 0) {
        $validator = Validator::make($request->all(), [
            'account_number' => 'required|string',
            'account_name'   => 'required|string',
            'short_name'     => 'required|string',
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $beneficiaryUser = User::where('account_number', $request->account_number)->where('username', $request->account_name)->first();

        if (!$beneficiaryUser) {
            $notify[] = 'Beneficiary account doesn\'t exists';
            return responseError('validation_error', $notify);
        }

        $beneficiaryExist = Beneficiary::where('id', '!=', $id)
            ->where('user_id', auth()->id())
            ->where('beneficiary_type', User::class)
            ->where('beneficiary_id', $beneficiaryUser->id)
            ->exists();

        if ($beneficiaryExist) {
            $notify[] = 'This beneficiary already added';
            return responseError('validation_error', $notify);
        }

        if ($id) {
            $beneficiary = Beneficiary::where('user_id', auth()->id())->where('id', $id)->first();
            if (!$beneficiary) {
                $notify[] = 'Beneficiary account doesn\'t exists';
                return responseError('validation_error', $notify);
            }

            $notification = "Beneficiary updated successfully";
        } else {
            $beneficiary           = new Beneficiary();
            $notification          = "Beneficiary added successfully";
        }

        $beneficiary->user_id        = auth()->id();
        $beneficiary->account_number = $request->account_number;
        $beneficiary->account_name   = $request->account_name;
        $beneficiary->short_name     = $request->short_name;

        $beneficiaryUser->beneficiaryTypes()->save($beneficiary);

        $notify[] = $notification;
        return responseSuccess('beneficiary', $notify);
    }

    public function addOtherBeneficiary(Request $request, $id = 0) {

        $validator = Validator::make($request->all(), [
            'bank'           => 'required|integer',
            'account_number' => 'required|string',
            'short_name'     => 'required',
        ]);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        $bank = OtherBank::active()->find($request->bank);
        if (!$bank) {
            $notify[] = 'Bank not found';
            return responseError('validation_error', $notify);
        }

        $checkDuplicate = Beneficiary::otherBank()
            ->where('id', '!=', $id)
            ->where('user_id', auth()->id())
            ->where('beneficiary_id', $bank->id)
            ->where('account_number', $request->account_number)
            ->exists();


        if ($checkDuplicate) {
            $notify[] = 'Beneficiary already added with this account number';
            return responseError('validation_error', $notify);
        }

        $userData = null;
        if (@$bank->form->form_data) {
            $formData           = $bank->form->form_data;
            $formProcessor      = new FormProcessor();
            $validationRule     = $formProcessor->valueValidation($formData);
            $formDataValidation = Validator::make($request->all(), $validationRule);

            if ($formDataValidation->fails()) {
                return responseError('validation_error', $formDataValidation->errors());
            }
            $userData = $formProcessor->processFormData($request, $formData);
        }

        if ($id) {
            $beneficiary = Beneficiary::otherBank()->where('user_id', auth()->id())->where('id', $id)->first();
            if (!$beneficiary) {
                $notify[] = 'Beneficiary account doesn\'t exists';
                return responseError('validation_error', $notify);
            }

            $path        = getFilePath('verify');
            $fileManager = new FileManager();

            foreach ($beneficiary->details as $file) {
                if ($request->file() && $file->type == 'file') {
                    $fileManager->removeFile($path . '/' . $file->value);
                }
            }

            $notification = 'Beneficiary updated successfully';
        } else {
            $beneficiary = new Beneficiary();
            $notification = 'Beneficiary added successfully';
        }

        $beneficiary->user_id        = auth()->id();
        $beneficiary->account_number = $request->account_number;
        $beneficiary->account_name   = $request->account_name;
        $beneficiary->short_name     = $request->short_name;
        $beneficiary->details        = $userData;

        $bank->beneficiaryTypes()->save($beneficiary);

        $notify[] = $notification;
        return responseSuccess('beneficiary', $notify);
    }

    public function details($id) {
        $beneficiary = Beneficiary::where('id', $id)->first();

        if (!$beneficiary) {
            $notify[] = 'Beneficiary Not Found';
            return responseError('beneficiary_error', $notify);
        }
        $notify[] = 'Beneficiary Data';

        return responseSuccess('beneficiary', $notify, [
            'beneficiary' => $beneficiary,
        ]);
    }

    public function bankFormData(Request $request) {
        $bank = OtherBank::active()->where('id', $request->id)->first();
        if (!$bank) {
            $notify[] = 'Bank not found';
            return responseError('bank_not_found', $notify);
        }

        $formData = $bank->form->form_data;
        $notify[] = 'Bank form data';

        return responseSuccess('bank_data', $notify, [
            'html' => $formData,
        ]);
    }

    public function checkAccountNumber(Request $request) {
        $user = User::where('account_number', $request->account_number)->orWhere('username', $request->account_name)->first();
        if (!$user || @$user->id == auth()->id()) {
            $notify[] = 'No such account found';
            return responseError('check_account_number', $notify);
        }

        $data = [
            'account_number' => $user->account_number,
            'account_name'   => $user->username,
        ];

        $notify[] = 'Account found';
        return responseSuccess('account_found', $notify, [
            'user' => $data,
        ]);
    }
}
