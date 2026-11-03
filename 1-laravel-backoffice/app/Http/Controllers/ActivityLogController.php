<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

/**
 * Denetim izi: kim, neyi, ne zaman oluşturdu/değiştirdi/sildi.
 * Yalnızca yöneticiler erişir.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage', \App\Models\User::class), 403);

        $query = ActivityLog::with('user')
            ->where('company_id', $request->user()->company_id)
            ->latest('id');

        if ($action = $request->get('action')) {
            $query->where('action', $action);
        }

        if ($modelType = $request->get('model')) {
            $query->where('model_type', $modelType);
        }

        return view('activity.index', [
            'logs' => $query->paginate(25)->withQueryString(),
            'filters' => ['action' => $action, 'model' => $modelType],
        ]);
    }
}
