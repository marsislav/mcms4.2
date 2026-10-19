<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;

class MessageController extends Controller
{
    public function index()
    {
        return view('admin.messages.index', ['messages' => Message::latest('id')->paginate(25)]);
    }

    public function show(Message $message)
    {
        if (! $message->read_at) {
            $message->update(['read_at' => now()]);
        }

        return view('admin.messages.show', ['message' => $message]);
    }

    public function destroy(Message $message)
    {
        audit('message.delete', (string) $message->id);
        $message->delete();

        return redirect()->route('admin.messages.index')->with('status', __('Съобщението е изтрито.'));
    }
}
