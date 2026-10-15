<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Support\Uploader;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $items = Media::latest('id')
            ->when($request->filled('q'), fn ($q) => $q->where('original_name', 'like', '%'.addcslashes($request->string('q')->limit(80), '%_\\').'%'))
            ->paginate(24)->withQueryString();

        return view('admin.media.index', ['items' => $items]);
    }

    public function store(Request $request)
    {
        $request->validate(['files' => 'required|array|max:20', 'files.*' => 'file']);

        $ok = 0;
        $errors = [];
        foreach ($request->file('files') as $file) {
            try {
                $media = Uploader::store($file, $request->user()->id);
                audit('media.upload', (string) $media->id, $media->original_name);
                $ok++;
            } catch (\Throwable $e) {
                $errors[] = $file->getClientOriginalName().': '.$e->getMessage();
            }
        }

        $redirect = back();
        if ($ok) {
            $redirect->with('status', "Качени файлове: $ok.");
        }

        return $errors ? $redirect->with('error', implode(' | ', $errors)) : $redirect;
    }

    public function update(Request $request, Media $media)
    {
        $data = $request->validate(['alt' => 'nullable|string|max:190']);
        $media->update(['alt' => $data['alt'] ? strip_tags($data['alt']) : null]);

        return back()->with('status', 'Записано.');
    }

    public function destroy(Media $media)
    {
        audit('media.delete', (string) $media->id, $media->original_name);
        Uploader::delete($media);

        return back()->with('status', 'Файлът е изтрит.');
    }

    /** JSON feed for the in-editor media picker. */
    public function picker(Request $request)
    {
        $items = Media::latest('id')
            ->when($request->boolean('images'), fn ($q) => $q->where('mime', 'like', 'image/%'))
            ->when($request->filled('q'), fn ($q) => $q->where('original_name', 'like', '%'.addcslashes($request->string('q')->limit(80), '%_\\').'%'))
            ->paginate(30);

        return response()->json([
            'data' => $items->map(fn (Media $m) => [
                'id' => $m->id,
                'name' => $m->original_name,
                'url' => $m->url(),
                'thumb' => $m->thumbUrl(),
                'image' => $m->isImage(),
                'alt' => $m->alt,
            ]),
            'next' => $items->nextPageUrl(),
        ]);
    }
}
