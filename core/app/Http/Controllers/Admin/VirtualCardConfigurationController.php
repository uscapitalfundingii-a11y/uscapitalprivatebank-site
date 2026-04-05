<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;

class VirtualCardConfigurationController extends Controller
{
    public function configure()
    {
        $pageTitle = 'Virtual Card Configuration';
        return view('admin.setting.virtual_card', compact('pageTitle'));
    }

    public function updateConfiguration(Request $request)
    {
        $request->validate([
            'stripe_secret_key'       => 'required|string',
            'stripe_publishable_key'  => 'required|string',
            'webhook_endpoint_secret' => 'required|string',
            'card_issue_fee'     => 'required|numeric|min:0',
            'yearly_card_charge' => 'required|numeric|min:0',
            'text_color'      => 'required',
            'card_background' => ['image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);


        $general = gs();
        $brandingConf = $general->branding_config;

        // strip config
        $general->stripe_secret_key       = $request->stripe_secret_key;
        $general->stripe_publishable_key  = $request->stripe_publishable_key;
        $general->webhook_endpoint_secret = $request->webhook_endpoint_secret;

        // card issue
        $general->card_issue_fee     = $request->card_issue_fee;
        $general->yearly_card_charge = $request->yearly_card_charge;

        // branding
        $brandingConf->text_color = $request->text_color;
        if ($request->hasFile('card_background')) {
            try {
                $brandingConf->background = fileUploader($request->card_background, getFilePath('cardBackground'), getFileSize('cardBackground'), @$brandingConf->background);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your image'];
                return back()->withNotify($notify);
            }
        }
        $general->branding_config = $brandingConf;

        $general->save();

        $notify[] = ['success', 'Virtual card configuration updated successfully'];
        return back()->withNotify($notify);
    }
}
