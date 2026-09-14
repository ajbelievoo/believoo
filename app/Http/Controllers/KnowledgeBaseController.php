<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeArticle;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request)
    {
        return redirect('https://support.believoo.com/help');
    }

    public function show(KnowledgeArticle $article)
    {
        return redirect('https://support.believoo.com/help/' . $article->slug);
    }
}
