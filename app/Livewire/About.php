<?php

namespace App\Livewire;

use App\Models\PageContent;
use Livewire\Component;

class About extends Component
{
    public function render()
    {
        return view('livewire.about', [
            'content' => PageContent::where('page_name', 'about')->pluck('value', 'key'),
            'team' => \App\Models\Team::where('is_active', true)->orderBy('order', 'asc')->get()
        ])->layout('components.layouts.believoo', [
            'title' => 'About Believoo - Cloud, VPS & Software Company in India',
            'description' => 'Believoo is a cloud infrastructure and software company helping businesses with VPS hosting, web hosting, live streaming and custom software solutions.',
            'keywords' => 'About Believoo, cloud company India, VPS hosting company, software agency',
            'settings' => \App\Models\Setting::pluck('value', 'key')
        ]);
    }
}
