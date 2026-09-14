<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ChatArchive;
use App\Models\Message;
use Illuminate\Http\Request;
class ChatArchiveController extends Controller {
    public function index(Request $r) {
        $q = ChatArchive::query();
        if ($r->search) $q->where('client_email', 'like', "%{$r->search}%")->orWhere('session_id', 'like', "%{$r->search}%")->orWhere('transcript', 'like', "%{$r->search}%");
        if ($r->date) $q->whereDate('created_at', $r->date);
        return view('admin.chat-archive.index', ['archives' => $q->latest()->paginate(25)]);
    }
    public function export(Request $r) {
        $rows = ChatArchive::latest()->limit(5000)->get();
        $headers = ['ID', 'Session', 'Name', 'Email', 'Tags', 'Rating', 'Created', 'Transcript', 'Summary'];
        $callback = function() use ($rows, $headers) {
            $f = fopen('php://output', 'w');
            fputcsv($f, $headers);
            foreach ($rows as $row) fputcsv($f, [$row->id, $row->session_id, $row->client_name, $row->client_email, $row->tags, $row->rating, $row->created_at, $row->transcript, $row->summary]);
            fclose($f);
        };
        return response()->stream($callback, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="chat-archive.csv"']);
    }
}
