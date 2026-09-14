<?php
namespace App\Http\Controllers;
use App\Models\KnowledgeArticle;
use Illuminate\Http\Request;
class FaqController extends Controller {
    public function index(Request $r) {
        return redirect('https://support.believoo.com/help');
    }
}
