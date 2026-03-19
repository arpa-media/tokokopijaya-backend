<?php

namespace App\Support\HrSync;

use App\Models\Outlet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OutletCompatibilityPreserver
{
    /**
     * @param  Collection<int, string>|array<int, string>  $hrOutletCodes
     * @return array<string, mixed>
     */
    public function reconcile(Collection|array $hrOutletCodes = [], bool $dryRun = false): array
    {
        $hrOutletCodes = collect($hrOutletCodes)
            ->filter(fn ($code) => is_string($code) && trim($code) !== '')
            ->map(fn ($code) => trim((string) $code))
            ->values();

        $summary = [
            'scanned' => 0,
            'stubbed' => 0,
            'archived' => 0,
            'untouched' => 0,
            'rows' => [],
        ];

        $runner = function () use ($hrOutletCodes, &$summary): void {
            Outlet::query()
                ->where(function ($query) {
                    $query->whereNull('hr_outlet_id')->orWhere('is_hr_source', false);
                })
                ->orderBy('code')
                ->chunkById(100, function ($outlets) use ($hrOutletCodes, &$summary) {
                    foreach ($outlets as $outlet) {
                        $summary['scanned']++;

                        $counts = $this->referenceCounts((string) $outlet->id);
                        $totalReferences = $counts['outlet_product'] + $counts['product_variant_prices'] + $counts['sales'] + $counts['assignments'] + $counts['users'];
                        $hasHrCodeMatch = $outlet->code ? $hrOutletCodes->contains($outlet->code) : false;

                        $action = 'untouched';
                        if (! $hasHrCodeMatch && $totalReferences > 0) {
                            $outlet->forceFill([
                                'is_compatibility_stub' => true,
                                'is_active' => false,
                            ])->save();
                            $summary['stubbed']++;
                            $action = 'stubbed';
                        } elseif (! $hasHrCodeMatch && $totalReferences === 0) {
                            $outlet->forceFill([
                                'is_compatibility_stub' => false,
                                'is_active' => false,
                            ])->save();
                            $summary['archived']++;
                            $action = 'archived';
                        } else {
                            $summary['untouched']++;
                        }

                        $summary['rows'][] = [
                            'outlet_id' => (string) $outlet->id,
                            'code' => $outlet->code,
                            'name' => $outlet->name,
                            'has_hr_code_match' => $hasHrCodeMatch,
                            'is_hr_source' => (bool) $outlet->is_hr_source,
                            'references' => $counts,
                            'action' => $action,
                        ];
                    }
                }, 'id');
        };

        if ($dryRun) {
            DB::beginTransaction();
            try {
                $runner();
                DB::rollBack();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            return $summary;
        }

        DB::transaction($runner);

        return $summary;
    }

    public function referenceCounts(string $outletId): array
    {
        return [
            'outlet_product' => (int) DB::table('outlet_product')->where('outlet_id', $outletId)->count(),
            'product_variant_prices' => (int) DB::table('product_variant_prices')->where('outlet_id', $outletId)->count(),
            'sales' => (int) DB::table('sales')->where('outlet_id', $outletId)->count(),
            'assignments' => (int) DB::table('assignments')->where('outlet_id', $outletId)->count(),
            'users' => (int) DB::table('users')->where('outlet_id', $outletId)->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function report(Collection|array $hrOutletCodes = []): array
    {
        $hrOutletCodes = collect($hrOutletCodes)
            ->filter(fn ($code) => is_string($code) && trim($code) !== '')
            ->map(fn ($code) => trim((string) $code));

        return Outlet::query()
            ->orderBy('code')
            ->get()
            ->map(function (Outlet $outlet) use ($hrOutletCodes): array {
                $counts = $this->referenceCounts((string) $outlet->id);

                return [
                    'outlet_id' => (string) $outlet->id,
                    'code' => $outlet->code,
                    'name' => $outlet->name,
                    'type' => $outlet->type,
                    'is_hr_source' => (bool) $outlet->is_hr_source,
                    'is_compatibility_stub' => (bool) $outlet->is_compatibility_stub,
                    'is_active' => (bool) ($outlet->is_active ?? true),
                    'has_hr_code_match' => $outlet->code ? $hrOutletCodes->contains($outlet->code) : false,
                    'references' => $counts,
                ];
            })
            ->all();
    }
}
