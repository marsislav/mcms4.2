<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LanguageController extends Controller
{
    public function index()
    {
        return view('admin.languages.index', ['languages' => Language::orderBy('sort')->orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'regex:/^[a-z]{2}(-[A-Z]{2})?$/', 'unique:languages,code'],
            'name' => 'required|string|max:60',
            'native_name' => 'nullable|string|max:60',
        ]);

        $lang = Language::create($data + ['is_default' => false, 'is_active' => true, 'sort' => (int) Language::max('sort') + 1]);
        $this->flush();
        audit('language.create', $lang->code);

        return back()->with('status', 'Езикът е добавен. Преведете интерфейса от „Преводи“.');
    }

    public function update(Request $request, Language $language)
    {
        $data = $request->validate([
            'name' => 'required|string|max:60',
            'native_name' => 'nullable|string|max:60',
            'sort' => 'nullable|integer|min:0|max:1000',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($language, $data) {
            $makeDefault = ! empty($data['is_default']) || $language->is_default;
            $active = $makeDefault ? true : ! empty($data['is_active']);

            if ($makeDefault && ! $language->is_default) {
                Language::where('id', '!=', $language->id)->update(['is_default' => false]);
            }

            $language->update([
                'name' => $data['name'],
                'native_name' => $data['native_name'] ?? null,
                'sort' => (int) ($data['sort'] ?? $language->sort),
                'is_default' => $makeDefault,
                'is_active' => $active,
            ]);
        });

        $this->flush();
        audit('language.update', $language->code);

        return back()->with('status', 'Езикът е обновен.');
    }

    public function destroy(Language $language)
    {
        abort_if($language->is_default, 422, 'Основният език не може да се изтрие.');

        Translation::where('locale', $language->code)->delete();
        \App\Models\ContentTranslation::where('locale', $language->code)->delete();
        \App\Models\CategoryTranslation::where('locale', $language->code)->delete();
        $language->delete();
        $this->flush();
        audit('language.delete', $language->code);

        return back()->with('status', 'Езикът и неговото съдържание са изтрити.');
    }

    private function flush(): void
    {
        Language::flush();
        foreach (Language::pluck('code') as $c) {
            Translation::flush($c);
        }
    }
}
