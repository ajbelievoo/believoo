<?php

namespace App\Livewire;

use App\Models\Portfolio;
use Livewire\Component;

class PortfolioDetail extends Component
{
    public Portfolio $portfolio;

    public function mount(Portfolio $portfolio)
    {
        $this->portfolio = $portfolio;
    }

    public function render()
    {
        $description = !empty($this->portfolio->description)
            ? \Illuminate\Support\Str::limit(strip_tags($this->portfolio->description), 160)
            : null;

        return view('livewire.portfolio-detail')
            ->layout('components.layouts.believoo', [
                'title' => $this->portfolio->title . ' - Portfolio Project',
                'description' => $description,
                'keywords' => $this->portfolio->title . ', portfolio, Believoo, ' . $this->portfolio->category,
                'settings' => \App\Models\Setting::pluck('value', 'key')
            ]);
    }
}
