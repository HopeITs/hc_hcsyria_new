<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminCapability;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Queries\AdminAccessQuery;
use App\Queries\AdminProjectIndexQuery;
use App\Support\Admin\Csv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/*
 * CSV downloads of the staff lists: the micro projects (with the filters and tab on the
 * page, limited to the account's cities) and the newsletter subscribers (those who may
 * see the list; addresses are personal data).
 */
final class ListExportController extends Controller
{
    public function projects(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Project::class);

        $rows = AdminProjectIndexQuery::make()
            ->limitedTo(AdminAccessQuery::placeIds($request->user()))
            ->search($request->string('search')->limit(100)->toString())
            ->inCity($request->input('city'))
            ->inCategory($request->input('category'))
            ->forDonor($request->input('donor'))
            ->tab($request->string('tab')->toString())
            ->export();

        return Csv::download('micro-projects', ['Number', 'Name', 'City', 'Type', 'Category', 'Sub category', 'Donor', 'Funded', 'Online', 'Hidden', 'English checked', 'Photos'], $rows->map(fn ($r): array => [
            $r->number, $r->name, $r->city, $r->kind, $r->category, $r->sub_category, $r->donor, $r->funded,
            $r->published === '1' ? 'Yes' : 'No', $r->hidden ? 'Yes' : 'No', $r->english_check ? 'Yes' : 'No', $r->photos,
        ]));
    }

    public function subscribers(Request $request): StreamedResponse
    {
        Gate::authorize('admin.'.AdminCapability::ViewSubscribers->value);

        $search = trim($request->string('search')->limit(100)->toString());

        $rows = DB::table('newsletter_subscribers')
            ->when($search !== '', fn ($q) => $q->where('mail', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->orderByDesc('id')
            ->get(['mail', 'active', 'created_at'])
            ->map(fn ($r): array => [$r->mail, $r->active ? 'Active' : 'Left', $r->created_at]);

        return Csv::download('newsletter-subscribers', ['Address', 'Status', 'Subscribed'], $rows);
    }
}
