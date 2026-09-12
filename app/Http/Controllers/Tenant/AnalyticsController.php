<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Services\Report\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    /**
     * 1. Executive Analytics & Portfolio KPI Dashboard.
     */
    public function dashboard(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = $request->get('branch_id');

        $kpis = $this->analyticsService->getExecutiveKpis($companyId, $branchId ? (int) $branchId : null);
        $branches = Branch::where('company_id', $companyId)->get();
        $branchPerformance = $this->analyticsService->getBranchPerformance($companyId);
        $productStats = $this->analyticsService->getProductCategoryAnalytics($companyId, $branchId ? (int) $branchId : null);
        $collectionEfficiency = $this->analyticsService->getCollectionEfficiency($companyId, $branchId ? (int) $branchId : null, 6);

        return view('tenant.analytics.dashboard', compact(
            'kpis',
            'branches',
            'branchId',
            'branchPerformance',
            'productStats',
            'collectionEfficiency'
        ));
    }

    /**
     * 2. Portfolio Aging Bucket Report & PAR Analysis.
     */
    public function aging(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = $request->get('branch_id');
        $categoryId = $request->get('category_id');
        $asOfDate = $request->get('as_of_date', Carbon::today()->toDateString());

        $report = $this->analyticsService->getAgingReport(
            $companyId,
            $branchId ? (int) $branchId : null,
            $categoryId ? (int) $categoryId : null,
            $asOfDate
        );

        $branches = Branch::where('company_id', $companyId)->get();
        $categories = ProductCategory::where('company_id', $companyId)->get();

        return view('tenant.analytics.aging', compact(
            'report',
            'branches',
            'categories',
            'branchId',
            'categoryId',
            'asOfDate'
        ));
    }

    /**
     * 3. Collection Efficiency & Cashier Recovery Report.
     */
    public function collections(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = $request->get('branch_id');
        $months = (int) $request->get('months', 6);

        $report = $this->analyticsService->getCollectionEfficiency(
            $companyId,
            $branchId ? (int) $branchId : null,
            $months
        );

        $branches = Branch::where('company_id', $companyId)->get();

        return view('tenant.analytics.collections', compact('report', 'branches', 'branchId', 'months'));
    }

    /**
     * 4. Multi-Branch Showroom Comparative Leaderboard.
     */
    public function branches()
    {
        $companyId = Auth::user()->company_id;
        $branches = $this->analyticsService->getBranchPerformance($companyId);

        return view('tenant.analytics.branches', compact('branches'));
    }

    /**
     * 5. Appliance Category & Brand Profitability Analysis.
     */
    public function products(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = $request->get('branch_id');

        $categories = $this->analyticsService->getProductCategoryAnalytics(
            $companyId,
            $branchId ? (int) $branchId : null
        );

        $branches = Branch::where('company_id', $companyId)->get();

        return view('tenant.analytics.products', compact('categories', 'branches', 'branchId'));
    }

    /**
     * 6. Export Analytics to CSV.
     */
    public function export(Request $request, string $type)
    {
        $companyId = Auth::user()->company_id;

        if (! in_array($type, ['aging', 'branches', 'collections'])) {
            abort(404, 'Unknown export report type.');
        }

        $filters = [
            'branch_id' => $request->get('branch_id'),
            'category_id' => $request->get('category_id'),
            'as_of_date' => $request->get('as_of_date'),
        ];

        return $this->analyticsService->exportCsv($type, $companyId, $filters);
    }
}
