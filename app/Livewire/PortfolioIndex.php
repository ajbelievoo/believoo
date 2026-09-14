<?php

namespace App\Livewire;

use App\Models\Portfolio;
use Livewire\Component;

class PortfolioIndex extends Component
{
    public function render()
    {
        return view('livewire.portfolio-index', [
            'portfolios' => Portfolio::where('is_visible', true)->get()
        ])->layout('components.layouts.believoo', [
            'title' => 'Portfolio - Projects by Believoo',
            'description' => 'Explore our portfolio of cloud infrastructure, web applications, mobile apps and software projects built for clients worldwide.',
            'keywords' => 'Believoo portfolio, software projects, web development portfolio, India',
            'settings' => \App\Models\Setting::pluck('value', 'key')
        ]);
    }
}
