<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminCapability;
use App\Http\Controllers\Controller;
use App\Queries\AdminAccessQuery;
use App\Queries\AdminStatisticsQuery;
use App\Support\Admin\Csv;
use App\Support\Admin\Reports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/*
 * Downloads one report as CSV, with the same city and year filters as the page and only
 * for the cities the account may see. Opens in Excel with Arabic names intact (UTF-8 BOM).
 */
final class ReportExportController extends Controller
{
    public function __invoke(Request $request, string $report): StreamedResponse
    {
        Gate::authorize('admin.'.AdminCapability::ViewReports->value);
        abort_unless(Reports::exists($report), 404);

        $user = $request->user();
        $places = AdminAccessQuery::placeIds($user);
        $city = $request->integer('city') ?: null;
        abort_if($city !== null && ! AdminAccessQuery::mayUsePlace($user, $city), 403);
        $year = $request->integer('year') ?: null;

        $table = Reports::build($report, new AdminStatisticsQuery($places, $city, $year));

        return Csv::download($report, $table['headings'], $table['rows']);
    }
}
