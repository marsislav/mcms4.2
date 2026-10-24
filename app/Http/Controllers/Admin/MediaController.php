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
            $redirect->with('status', __('Качени файлове: :n.', ['n' => $ok]));
        }

        return $errors ? $redirect->with('error', implode(' | ', $errors)) : $redirect;
    }

    private function item(Media $m): array
    {
        return ['id' => $m->id, 'name' => $m->original_name, 'url' => $m->url(), 'thumb' => $m->thumbUrl(), 'image' => $m->isImage(), 'video' => $m->isVideo(), 'alt' => $m->alt];
    }

    /** AJAX upload from the media picker / featured-image field. Accepts files[] (or a single file). */
    public function upload(Request $request)
    {
        $request->validate(['files' => 'required|array|max:20', 'files.*' => 'file']);

        $items = [];
        $errors = [];
        foreach ($request->file('files') as $file) {
            try {
                $media = Uploader::store($file, $request->user()->id);
                audit('media.upload', (string) $media->id, $media->original_name);
                $items[] = $this->item($media);
            } catch (\Throwable $e) {
                $errors[] = $file->getClientOriginalName().': '.$e->getMessage();
            }
        }

        return response()->json(['data' => $items, 'errors' => $errors], $items ? 200 : 422);
    }

    /** CKEditor 5 SimpleUploadAdapter endpoint: field "upload" -> {url} or {error:{message}}. */
    public function editorUpload(Request $request)
    {
        $file = $request->file('upload');
        try {
            if (! $file || ! $file->isValid()) {
                throw new \RuntimeException(__('Качването е неуспешно.'));
            }
            $real = $file->getRealPath();
            if (! $real || ! str_starts_with((string) (new \finfo(FILEINFO_MIME_TYPE))->file($real), 'image/')) {
                throw new \RuntimeException(__('В текста могат да се качват само изображения (JPG, PNG, WebP, GIF).'));
            }
            $media = Uploader::store($file, $request->user()->id);
            audit('media.upload', (string) $media->id, $media->original_name);

            return response()->json(['url' => $media->url(), 'id' => $media->id]);
        } catch (\Throwable $e) {
            return response()->json(['error' => ['message' => $e->getMessage()]], 422);
        }
    }

    public function update(Request $request, Media $media)
    {
        $data = $request->validate(['alt' => 'nullable|string|max:190']);
        $media->update(['alt' => $data['alt'] ? strip_tags($data['alt']) : null]);

        return back()->with('status', __('Записано.'));
    }

    public function destroy(Media $media)
    {
        audit('media.delete', (string) $media->id, $media->original_name);
        Uploader::delete($media);

        return back()->with('status', __('Файлът е изтрит.'));
    }

    /** JSON feed for the in-editor media picker. */
    public function picker(Request $request)
    {
        $items = Media::latest('id')
            ->when($request->boolean('images'), fn ($q) => $q->where('mime', 'like', 'image/%'))
            ->when($request->boolean('videos'), fn ($q) => $q->where('mime', 'like', 'video/%'))
            ->when($request->filled('q'), fn ($q) => $q->where('original_name', 'like', '%'.addcslashes($request->string('q')->limit(80), '%_\\').'%'))
            ->paginate(30);

        return response()->json([
            'data' => $items->map(fn (Media $m) => [
                'id' => $m->id,
                'name' => $m->original_name,
                'url' => $m->url(),
                'thumb' => $m->thumbUrl(),
                'image' => $m->isImage(),
                'video' => $m->isVideo(),
                'alt' => $m->alt,
            ]),
            'next' => $items->nextPageUrl(),
        ]);
    }
}
