<?php

use App\Support\IdentityNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'branches' => [
                'code' => fn ($value) => IdentityNormalizer::branchCode($value),
            ],
            'vehicles' => [
                'number_plate' => fn ($value) => IdentityNormalizer::vehiclePlate($value),
                'chassis_number' => fn ($value) => IdentityNormalizer::serialNumber($value),
                'engine_number' => fn ($value) => IdentityNormalizer::serialNumber($value),
            ],
            'customers' => [
                'email' => fn ($value) => IdentityNormalizer::email($value),
                'phone' => fn ($value) => IdentityNormalizer::indonesianPhone($value),
            ],
        ];

        foreach ($tables as $table => $normalizers) {
            $this->normalizeWithoutChangingRowCount($table, $normalizers);
        }

        Schema::table('branches', function (Blueprint $table): void {
            $table->unique('code', 'branches_code_unique');
        });
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->unique('number_plate', 'vehicles_number_plate_unique');
            $table->unique('chassis_number', 'vehicles_chassis_number_unique');
            $table->unique('engine_number', 'vehicles_engine_number_unique');
        });
        Schema::table('customers', function (Blueprint $table): void {
            $table->unique('email', 'customers_email_unique');
            $table->unique('phone', 'customers_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique('customers_email_unique');
            $table->dropUnique('customers_phone_unique');
        });
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropUnique('vehicles_number_plate_unique');
            $table->dropUnique('vehicles_chassis_number_unique');
            $table->dropUnique('vehicles_engine_number_unique');
        });
        Schema::table('branches', function (Blueprint $table): void {
            $table->dropUnique('branches_code_unique');
        });
    }

    private function normalizeWithoutChangingRowCount(string $table, array $normalizers): void
    {
        $before = DB::table($table)->count();
        $rows = DB::table($table)->orderBy('id')->get(['id', ...array_keys($normalizers)]);
        $seen = [];
        $updates = [];

        foreach ($rows as $row) {
            $changes = [];
            foreach ($normalizers as $column => $normalize) {
                $normalized = $normalize($row->{$column});
                if ($normalized !== null) {
                    $key = $column."\0".$normalized;
                    if (isset($seen[$key]) && $seen[$key] !== $row->id) {
                        throw new RuntimeException(
                            "Tidak dapat membuat unique index {$table}.{$column}: row ID {$seen[$key]} dan {$row->id} menjadi identifier {$normalized}. Perbaiki kedua row berdasarkan primary key tanpa menghapus/merge data."
                        );
                    }
                    $seen[$key] = $row->id;
                }
                if ($row->{$column} !== $normalized) {
                    $changes[$column] = $normalized;
                }
            }
            if ($changes !== []) {
                $updates[$row->id] = $changes;
            }
        }

        foreach ($updates as $id => $changes) {
            DB::table($table)->where('id', $id)->update($changes);
        }

        if (DB::table($table)->count() !== $before) {
            throw new RuntimeException("Normalisasi {$table} mengubah jumlah row; migration dibatalkan.");
        }
    }
};
