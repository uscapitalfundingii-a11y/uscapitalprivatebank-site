<?php

namespace App\Console\Commands;

use App\Models\Form;
use App\Models\OtherBank;
use App\Support\BankCoordinateDirectory;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncBankCoordinates extends Command
{
    protected $signature = 'bank:sync-coordinates {--dry-run : Show what would change without saving}';

    protected $description = 'Sync the .org receiving bank coordinate directory into .com Other Banks records.';

    public function handle(): int
    {
        $created = 0;
        $updated = 0;
        $dryRun  = (bool) $this->option('dry-run');

        foreach (BankCoordinateDirectory::all() as $coordinate) {
            $bank = OtherBank::where('name', $coordinate['name'])->first();
            $instruction = $this->instruction($coordinate);

            if (!$bank) {
                $created++;

                if ($dryRun) {
                    $this->line('Would create bank: ' . $coordinate['name']);
                    continue;
                }

                $form = new Form();
                $form->act = 'other_bank';
                $form->form_data = $this->formData();
                $form->save();

                $bank = new OtherBank();
                $bank->name = $coordinate['name'];
                $bank->minimum_limit = 1;
                $bank->maximum_limit = 999999999999;
                $bank->daily_maximum_limit = 999999999999;
                $bank->monthly_maximum_limit = 999999999999;
                $bank->daily_total_transaction = 9999;
                $bank->monthly_total_transaction = 9999;
                $bank->fixed_charge = 0;
                $bank->percent_charge = 0;
                $bank->processing_time = 'Admin reviewed';
                $bank->form_id = $form->id;
                $bank->status = 1;
            } elseif ($bank->instruction !== $instruction) {
                $updated++;

                if ($dryRun) {
                    $this->line('Would update bank coordinates: ' . $coordinate['name']);
                    continue;
                }
            } else {
                continue;
            }

            $bank->instruction = $instruction;
            $bank->save();
        }

        $message = $dryRun ? 'Dry run complete' : 'Bank coordinate sync complete';
        $this->info("$message. Created: $created. Updated: $updated.");

        return self::SUCCESS;
    }

    private function instruction(array $coordinate): string
    {
        return implode('<br>', array_filter([
            'SWIFT/BIC: ' . ($coordinate['swift_code'] ?? ''),
            'Country: ' . ($coordinate['country'] ?? ''),
            'City: ' . ($coordinate['city'] ?? ''),
            'Address: ' . ($coordinate['address'] ?? ''),
            'Phone: ' . ($coordinate['phone'] ?? ''),
        ]));
    }

    private function formData(): array
    {
        $fields = [
            ['Account Name', 'required', '12'],
            ['Account Number', 'required', '12'],
            ['SWIFT / BIC', 'required', '6'],
            ['Routing Number', 'optional', '6'],
            ['Bank Country', 'required', '6'],
            ['Bank City', 'required', '6'],
            ['Bank Address', 'required', '12'],
            ['Bank Phone', 'optional', '6'],
        ];

        $formData = [];

        foreach ($fields as [$name, $required, $width]) {
            $formData[titleToKey($name)] = [
                'name' => $name,
                'label' => titleToKey($name),
                'is_required' => $required,
                'instruction' => '',
                'extensions' => '',
                'options' => [],
                'type' => 'text',
                'width' => $width,
            ];
        }

        return collect($formData)
            ->sortBy(fn ($field) => Str::startsWith($field['label'], 'account_') ? 0 : 1)
            ->all();
    }
}
