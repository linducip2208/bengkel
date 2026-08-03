<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerService extends BaseService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Customer::query();

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15)->withQueryString();
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer;
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
    }

    public function search(string $term)
    {
        return Customer::where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->limit(20)
            ->get(['id', 'name', 'email', 'phone']);
    }

    public function getWithStats(Customer $customer): array
    {
        return [
            'customer' => $customer,
            'vehicle_count' => $customer->vehicles()->count(),
            'total_services' => $customer->services()->count(),
            'total_spent' => $customer->invoices()->where('payment_status', 2)->sum('total_amount'),
        ];
    }

    public function importFromCsv(array $rows): int
    {
        $imported = 0;
        foreach ($rows as $row) {
            if (empty($row['name'])) {
                continue;
            }
            Customer::updateOrCreate(
                ['email' => $row['email'] ?? null],
                [
                    'name' => $row['name'],
                    'phone' => $row['phone'] ?? null,
                    'address' => $row['address'] ?? null,
                    'notes' => $row['notes'] ?? null,
                ]
            );
            $imported++;
        }
        return $imported;
    }
}
