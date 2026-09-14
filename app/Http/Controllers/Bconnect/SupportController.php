<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;

class SupportController extends Controller {
    public function index() {
        return view('bconnect.support');
    }
}
