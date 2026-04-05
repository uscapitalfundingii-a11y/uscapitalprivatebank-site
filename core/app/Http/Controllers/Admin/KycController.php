<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\KycDocument;
use App\Lib\FormProcessor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KycController extends Controller
{
    public function setting()
    {
        $pageTitle = 'KYC Setting';
        $form = Form::where('act','kyc')->first();
        $fileFields = collect($form?->form_data ?? [])
            ->filter(fn ($field) => ($field->type ?? null) === 'file')
            ->values();
        $documents = KycDocument::query()->orderBy('title')->get()->keyBy('field_label');

        return view('admin.kyc.setting',compact('pageTitle','form', 'fileFields', 'documents'));
    }

    public function settingUpdate(Request $request)
    {
        if ($request->input('kyc_document_action') === 'upload') {
            return $this->uploadDocument($request);
        }

        $formProcessor = new FormProcessor();
        $generatorValidation = $formProcessor->generatorValidation();
        $request->validate($generatorValidation['rules'],$generatorValidation['messages']);
        $exist = Form::where('act','kyc')->first();
        $formProcessor->generate('kyc',$exist,'act');

        $notify[] = ['success','KYC data updated successfully'];
        return back()->withNotify($notify);
    }

    protected function uploadDocument(Request $request)
    {
        $form = Form::where('act', 'kyc')->firstOrFail();
        $fileFields = collect($form->form_data ?? [])->keyBy('label');

        $request->validate([
            'field_label' => ['required', Rule::in($fileFields->keys()->all())],
            'document' => ['required', 'file', 'max:51200', new \App\Rules\FileTypeValidate((new FormProcessor())->supportedExt())],
        ]);

        $field = $fileFields[$request->field_label];
        $documentFile = $request->file('document');
        $extension = strtolower((string) $documentFile->getClientOriginalExtension());
        $title = KycDocument::stableTitle((string) $field->name);
        $slug = KycDocument::stableSlug((string) $field->name);

        $existingDocument = KycDocument::where('field_label', $request->field_label)->first();
        $oldFilename = $existingDocument?->stored_name;
        $storedName = $slug . '.' . $extension;

        fileUploader(
            $documentFile,
            getFilePath('verify') . '/kyc_documents',
            old: $oldFilename,
            filename: $storedName
        );

        KycDocument::updateOrCreate(
            ['field_label' => $request->field_label],
            [
                'field_name' => $field->name,
                'title' => $title,
                'slug' => $slug,
                'stored_name' => $storedName,
                'original_name' => $documentFile->getClientOriginalName(),
                'extension' => $extension,
                'mime_type' => $documentFile->getMimeType(),
                'status' => true,
            ]
        );

        $this->syncFieldExtensions($form, $request->field_label, $extension);

        $notify[] = ['success', $title . ' document updated successfully'];
        return back()->withNotify($notify);
    }

    protected function syncFieldExtensions(Form $form, string $fieldLabel, string $extension): void
    {
        $formData = json_decode(json_encode($form->form_data), true);

        if (!isset($formData[$fieldLabel])) {
            return;
        }

        $extensions = collect(explode(',', (string) ($formData[$fieldLabel]['extensions'] ?? '')))
            ->map(fn ($item) => strtolower(trim($item)))
            ->filter()
            ->push($extension)
            ->unique()
            ->values()
            ->implode(',');

        $formData[$fieldLabel]['extensions'] = $extensions;
        $form->form_data = $formData;
        $form->save();
    }
}
