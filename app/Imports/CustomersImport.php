<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class CustomersImport implements ToModel, WithHeadingRow, SkipsOnError
{
    use RemembersRowNumber;

    public int $imported = 0;

    public array $errors = [];

    public function model(array $row)
    {
        $name = trim((string) ($row['name'] ?? ''));
        $phone = $this->nullableString($row['phone'] ?? null);

        if ($name === '' && $phone === '') {
            return null;
        }

        try {
            $data = [
                'name' => $name,
                'phone' => $phone,
                'email' => $this->nullableString($row['email'] ?? null),
                'address' => $this->nullableString($row['address'] ?? null),
                'company_name' => $this->nullableString($row['company_name'] ?? null),
                'tax_id' => $this->nullableString($row['tax_id'] ?? null),
            ];

            if ($phone !== null) {
                $customer = Customer::withoutGlobalScopes()->firstOrCreate(
                    ['phone' => $phone],
                    $data
                );

                if (! $customer->wasRecentlyCreated) {
                    $customer->update($data);
                }
            } else {
                $customer = Customer::withoutGlobalScopes()->create($data);
            }

            $this->imported++;

            return $customer;
        } catch (Throwable $e) {
            $this->errors[] = 'Baris ' . ($this->getRowNumber() ?? '?') . ': ' . $e->getMessage();

            return null;
        }
    }

    public function onError(Throwable $e)
    {
        $this->errors[] = 'Baris ' . ($this->getRowNumber() ?? '?') . ': ' . $e->getMessage();
    }

    protected function nullableString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }
}
