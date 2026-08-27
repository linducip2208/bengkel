<?php

namespace App\Imports;

use App\Models\Customer;
use App\Support\IdentityNormalizer;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class CustomersImport implements SkipsOnError, ToModel, WithHeadingRow
{
    use RemembersRowNumber;

    public int $imported = 0;

    public array $errors = [];

    public function model(array $row)
    {
        $name = trim((string) ($row['name'] ?? ''));
        $phone = IdentityNormalizer::indonesianPhone($row['phone'] ?? null);
        $email = IdentityNormalizer::email($row['email'] ?? null);

        if ($name === '' && $phone === null && $email === null) {
            return null;
        }

        try {
            $data = [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'address' => $this->nullableString($row['address'] ?? null),
                'company_name' => $this->nullableString($row['company_name'] ?? null),
                'tax_id' => $this->nullableString($row['tax_id'] ?? null),
            ];

            $phoneMatch = $phone === null ? null : Customer::withoutGlobalScopes()->where('phone', $phone)->first();
            $emailMatch = $email === null ? null : Customer::withoutGlobalScopes()->where('email', $email)->first();

            if ($phoneMatch && $emailMatch && ! $phoneMatch->is($emailMatch)) {
                throw new \RuntimeException('Nomor telepon dan email terhubung ke dua pelanggan berbeda; baris tidak diimport.');
            }

            $customer = $phoneMatch ?? $emailMatch;
            if ($customer) {
                $customer->update($data);
            } else {
                $customer = Customer::withoutGlobalScopes()->create($data);
            }

            $this->imported++;

            return $customer;
        } catch (Throwable $e) {
            $this->errors[] = 'Baris '.($this->getRowNumber() ?? '?').': '.$e->getMessage();

            return null;
        }
    }

    public function onError(Throwable $e)
    {
        $this->errors[] = 'Baris '.($this->getRowNumber() ?? '?').': '.$e->getMessage();
    }

    protected function nullableString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }
}
