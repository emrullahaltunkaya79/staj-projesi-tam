<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ], ['name.required' => 'Kategori adı zorunludur.']);

        // Aynı isimde kategori varsa sessizce geç (inline ekleme)
        Category::firstOrCreate(
            ['name' => $validated['name'], 'company_id' => $request->user()->company_id],
            ['name' => $validated['name'], 'company_id' => $request->user()->company_id]
        );

        return back()->with('success', "'{$validated['name']}' kategorisi eklendi.");
    }

    public function destroy(Request $request, Category $category)
    {
        if (! $request->user()->isAdmin()) {
            return back()->with('error', 'Silme işlemi için yönetici yetkisi gerekiyor.');
        }

        $name = $category->name;
        $category->delete(); // ürünlerdeki category_id null olur

        return back()->with('success', "'{$name}' kategorisi silindi (ürünler kategorisiz kaldı).");
    }
}
