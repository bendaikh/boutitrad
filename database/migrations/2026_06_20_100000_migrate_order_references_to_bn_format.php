<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyOrders = DB::table('orders')
            ->where('reference', 'like', 'CMD-%')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'created_at']);

        if ($legacyOrders->isEmpty()) {
            return;
        }

        $sequencesByYear = DB::table('orders')
            ->where('reference', 'like', 'BN-%')
            ->pluck('reference')
            ->map(function (string $reference) {
                if (preg_match('/^BN-(\d{4})(\d{4})$/', $reference, $matches)) {
                    return [(int) $matches[1], (int) $matches[2]];
                }

                return null;
            })
            ->filter()
            ->groupBy(fn (array $pair) => $pair[0])
            ->map(fn ($pairs) => $pairs->max(fn (array $pair) => $pair[1]))
            ->all();

        foreach ($legacyOrders as $order) {
            $year = (int) date('Y', strtotime($order->created_at));
            $sequencesByYear[$year] = ($sequencesByYear[$year] ?? 0) + 1;
            $sequence = $sequencesByYear[$year];

            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'reference' => 'BN-'.$year.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
                ]);
        }
    }

    public function down(): void
    {
        // Irreversible: legacy CMD references are not restored.
    }
};
