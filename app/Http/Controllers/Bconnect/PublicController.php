<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Company;
use App\Models\Bconnect\Ticket;
use App\Services\BconnectMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicController extends Controller {
    private function portalCompany() {
        return Company::orderBy('id')->first(); // site owner's company
    }

    public function help() {
        $articles = DB::table('bconnect_kb_articles')->where('published', true)
            ->orderBy('sort')->orderBy('id')->get()->groupBy('category');
        return view('bconnect.public.help', compact('articles'));
    }

    public function kb() { return $this->help(); }

    public function kbArticle($slug) {
        $a = DB::table('bconnect_kb_articles')->where('slug', $slug)->where('published', true)->firstOrFail();
        DB::table('bconnect_kb_articles')->where('id', $a->id)->increment('views');
        return view('bconnect.public.kb-article', ['a' => $a]);
    }

    public function blog() {
        $posts = DB::table('bconnect_blog_posts')->where('published', true)->orderByDesc('published_at')->paginate(9);
        return view('bconnect.public.blog', compact('posts'));
    }

    public function post($slug) {
        $post = DB::table('bconnect_blog_posts')->where('slug', $slug)->where('published', true)->firstOrFail();
        return view('bconnect.public.blog-post', compact('post'));
    }

    public function portal() {
        return view('bconnect.public.portal');
    }

    public function portalSubmit(Request $r) {
        $r->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'subject' => 'required|string|max:190',
            'message' => 'required|string|max:5000',
        ]);
        $company = $this->portalCompany();
        if (!$company) abort(404);
        $token = Str::random(24);
        $ticket = Ticket::create([
            'company_id' => $company->id,
            'title' => $r->subject,
            'description' => "From: {$r->name} <{$r->email}>\n\n" . $r->message,
            'status' => 'open',
            'priority' => $r->priority === 'high' ? 'high' : 'normal',
            'source' => 'portal',
            'public_token' => $token,
            'reporter_name' => $r->name,
            'reporter_email' => $r->email,
        ]);
        return redirect()->route('bconnect.portal.track', ['token' => $token])
            ->with('created', $ticket->id);
    }

    public function portalTrack(Request $r) {
        $t = null;
        if ($r->token) {
            $t = Ticket::where('public_token', $r->token)->first();
        }
        return view('bconnect.public.portal-track', ['t' => $t, 'token' => $r->token]);
    }
}
