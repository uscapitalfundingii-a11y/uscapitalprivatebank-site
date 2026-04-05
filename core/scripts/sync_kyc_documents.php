<?php

use App\Models\Form;
use App\Models\KycDocument;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$basePath = base_path(getFilePath('verify') . '/kyc_documents');

$documents = [
    'asset_monetization_questionnaire' => [
        'field_name' => 'Asset Monetization Questionnaire',
        'title' => 'Asset Monetization Questionnaire',
        'slug' => 'asset-monetization-questionnaire',
        'stored_name' => 'asset-monetization-questionnaire.pdf',
        'original_name' => 'Asset Monetization New Client Questionnaire.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
    ],
    'asset_monetization_procedures' => [
        'field_name' => 'Asset Monetization Procedures',
        'title' => 'Asset Monetization Procedures',
        'slug' => 'asset-monetization-procedures',
        'stored_name' => 'asset-monetization-procedures.pdf',
        'original_name' => 'Asset Monetization Procedure 2023.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
    ],
    'authorization_to_verify' => [
        'field_name' => 'Authorization to Verify',
        'title' => 'Authorization to Verify',
        'slug' => 'authorization-to-verify',
        'stored_name' => 'authorization-to-verify.docx',
        'original_name' => 'Authorization to Verify Account.docx',
        'extension' => 'docx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],
    'client_intake_form_(cis)' => [
        'field_name' => 'Client Intake Form (CIS)',
        'title' => 'Client Intake Form (CIS)',
        'slug' => 'client-intake-form-cis',
        'stored_name' => 'client-intake-form-cis.docx',
        'original_name' => 'Client Intake Form 2024.docx',
        'extension' => 'docx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],
    'confirmation_of_principal_(brokered_transactions)' => [
        'field_name' => 'Confirmation of Principal (Brokered Transactions)',
        'title' => 'Confirmation of Principal (Brokered Transactions)',
        'slug' => 'confirmation-of-principal-brokered-transactions',
        'stored_name' => 'confirmation-of-principal-brokered-transactions.pdf',
        'original_name' => 'Confirmation of Principal 2024 Template.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
    ],
    'service_request_pre_qualification_questionnaire' => [
        'field_name' => 'Service Request Pre Qualification Questionnaire',
        'title' => 'Service Request Pre Qualification Questionnaire',
        'slug' => 'service-request-pre-qualification-questionnaire',
        'stored_name' => 'service-request-pre-qualification-questionnaire.pdf',
        'original_name' => 'Service Request Prequalification Fillable .pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
    ],
    'us_capital_signature_card' => [
        'field_name' => 'US Capital Signature Card',
        'title' => 'US Capital Signature Card',
        'slug' => 'us-capital-signature-card',
        'stored_name' => 'us-capital-signature-card.pdf',
        'original_name' => 'U. S. Capital trust Business_Signature_Card.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
    ],
    'consideration_in_the_law_of_contracts' => [
        'field_name' => 'Consideration in The Law of Contracts',
        'title' => 'Consideration in The Law of Contracts',
        'slug' => 'consideration-in-the-law-of-contracts',
        'stored_name' => 'consideration-in-the-law-of-contracts.pdf',
        'original_name' => 'What is Consideration in Law of Contracts 2024.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
    ],
    'smart_plan_agreement' => [
        'field_name' => 'Smart Plan Agreement',
        'title' => 'Smart Plan Agreement',
        'slug' => 'smart-plan-agreement',
        'stored_name' => 'smart-plan-agreement.pdf',
        'original_name' => 'Smartplan Agreement 2023.pdf',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
    ],
];

$form = Form::where('act', 'kyc')->firstOrFail();
$formData = json_decode(json_encode($form->form_data), true);

foreach ($documents as $fieldLabel => $document) {
    $fullPath = $basePath . '/' . $document['stored_name'];

    if (!file_exists($fullPath)) {
        echo "Missing file: {$document['stored_name']}\n";
        continue;
    }

    KycDocument::updateOrCreate(
        ['field_label' => $fieldLabel],
        array_merge($document, ['status' => true])
    );

    if (isset($formData[$fieldLabel])) {
        $extensions = collect(explode(',', (string) ($formData[$fieldLabel]['extensions'] ?? '')))
            ->map(fn ($item) => strtolower(trim($item)))
            ->filter()
            ->push($document['extension'])
            ->unique()
            ->values()
            ->implode(',');

        $formData[$fieldLabel]['extensions'] = $extensions;
    }

    echo "Synced: {$document['title']}\n";
}

$form->form_data = $formData;
$form->save();

KycDocument::clearLibraryCache();

echo "Done\n";
