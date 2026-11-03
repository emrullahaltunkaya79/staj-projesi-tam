<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Http\Requests\ExpenseRequest;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::query()->latest('expense_date')->latest('id');

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        return view('expenses.index', [
            'expenses' => $query->paginate(9)->withQueryString(),
            'categories' => Expense::CATEGORIES,
            'filters' => ['category' => $category],
        ]);
    }

    public function create()
    {
        return view('expenses.create', [
            'expense' => new Expense([
                'category' => 'Kira',
                'expense_date' => now()->toDateString(),
                'payment_type' => 'nakit',
            ]),
        ]);
    }

    public function store(ExpenseRequest $request)
    {
        Expense::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Gider kaydı oluşturuldu.']);
        }

        return redirect()->route('giderler.index')
            ->with('success', 'Gider kaydı oluşturuldu.');
    }

    public function destroy(Request $request, Expense $expense)
    {
        if (! $request->user()->can('delete', $expense)) {
            return redirect()->route('giderler.index')
                ->with('error', 'Silme işlemi için yönetici yetkisi gerekiyor.');
        }

        $expense->delete();

        return redirect()->route('giderler.index')
            ->with('success', 'Gider kaydı silindi.');
    }
}
