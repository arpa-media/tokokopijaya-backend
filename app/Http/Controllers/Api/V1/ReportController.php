<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reports\LedgerReportRequest;
use App\Http\Requests\Api\V1\Reports\RecentSalesReportRequest;
use App\Http\Requests\Api\V1\Reports\RoundingReportRequest;
use App\Http\Requests\Api\V1\Reports\ReportRangeRequest;
use App\Http\Requests\Api\V1\Reports\DiscountReportRequest;
use App\Http\Requests\Api\V1\Reports\TaxReportRequest;
use App\Http\Requests\Api\V1\Reports\CashierReportRequest;
use App\Services\MarkingService;
use Illuminate\Http\Request;
use App\Http\Requests\Api\V1\Reports\UpdateMarkingSettingRequest;
use App\Http\Requests\Api\V1\Reports\MarkingReportRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{

    public function cashierReport(CashierReportRequest $request, ReportService $service): JsonResponse
    {
        $data = $service->cashierReport($request->validated(), $request->user()?->outlet_id);
        return response()->json(['data' => $data]);
    }

    public function cashierReportCashiers(CashierReportRequest $request, ReportService $service): JsonResponse
    {
        $data = $service->cashierReportCashiers($request->validated(), $request->user()?->outlet_id);
        return response()->json(['data' => $data]);
    }

    public function cashierReportByCashier(CashierReportRequest $request, string $cashierId, ReportService $service): JsonResponse
    {
        $params = $request->validated();
        $params['cashier_id'] = $cashierId;
        $data = $service->cashierReport($params, $request->user()?->outlet_id);
        return response()->json(['data' => $data]);
    }
    public function ledger(LedgerReportRequest $request, ReportService $service): JsonResponse
    {
        $data = $service->ledger($request->validated(), $request->user()?->outlet_id);
        return response()->json(['data' => $data]);
    }

    public function marking(MarkingReportRequest $request, ReportService $service): JsonResponse
    {
        $data = $service->marking($request->validated(), $request->user()?->outlet_id);
        return response()->json(['data' => $data]);
    }

    public function markingConfig(Request $request, MarkingService $service): JsonResponse
    {
        $outletId = $request->user()?->outlet_id;
        if (!$outletId) {
            $outletId = $request->header('X-Outlet-Id');
        }

        return response()->json(['data' => $service->getConfigPayload((string) $outletId)]);
    }

    public function updateMarkingConfig(UpdateMarkingSettingRequest $request, MarkingService $service): JsonResponse
    {
        $outletId = $request->user()?->outlet_id;
        if (!$outletId) {
            $outletId = $request->header('X-Outlet-Id');
        }

        $payload = $service->applyMode(
            (string) $outletId,
            (string) $request->input('status'),
            $request->filled('interval') ? (int) $request->input('interval') : null,
        );

        return response()->json(['data' => $payload]);
    }

    public function toggleMarking(Request $request, string $saleId, MarkingService $service): JsonResponse
    {
        $outletId = $request->user()?->outlet_id;
        if (!$outletId) {
            $outletId = $request->header('X-Outlet-Id');
        }

        return response()->json(['data' => $service->toggleSale((string) $outletId, $saleId)]);
    }

    public function itemSold(ReportRangeRequest $request, ReportService $service): JsonResponse
    {
        $data = $service->itemSold($request->validated(), $request->user()?->outlet_id);
        return response()->json(['data' => $data]);
    }

    public function recentSales(RecentSalesReportRequest $request, ReportService $service): JsonResponse
    {
        $data = $service->recentSales($request->validated(), $request->user()?->outlet_id);
        return response()->json(['data' => $data]);
    }

    public function itemByProduct(ReportRangeRequest $request, ReportService $service): JsonResponse
    {
        $data = $service->itemByProduct($request->validated(), $request->user()?->outlet_id);
        return response()->json(['data' => $data]);
    }

    public function itemByVariant(ReportRangeRequest $request, ReportService $service): JsonResponse
    {
        $data = $service->itemByVariant($request->validated(), $request->user()?->outlet_id);
        return response()->json(['data' => $data]);
    }

    public function rounding(RoundingReportRequest $request, ReportService $service): JsonResponse
    {
        $data = $service->rounding($request->validated(), $request->user()?->outlet_id);
        return response()->json(['data' => $data]);
    }

    public function tax(TaxReportRequest $request, ReportService $service): JsonResponse
    {
        $data = $service->tax($request->validated(), $request->user()?->outlet_id);
        return response()->json(['data' => $data]);
    }

    public function discount(DiscountReportRequest $request, ReportService $service): JsonResponse
    {
        $data = $service->discount($request->validated(), $request->user()?->outlet_id);
        return response()->json(['data' => $data]);
    }
}
