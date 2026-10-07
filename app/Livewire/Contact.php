<?php

namespace App\Livewire;

use Livewire\Component;

class Contact extends Component
{
    public function mount()
    {
        return redirect('https://support.believoo.com/contact');
    }

    public function render()
    {
        return view('livewire.contact', [
            'settings' => \App\Models\Setting::pluck('value', 'key')
        ])->layout('components.layouts.believoo', [
            'title' => 'Contact Believoo - VPS, Hosting & Streaming Support',
            'description' => 'Get in touch with Believoo for VPS hosting, web hosting, live streaming and software solutions. expert support available.',
            'keywords' => 'Contact Believoo, VPS support, hosting support, streaming support',
            'settings' => \App\Models\Setting::pluck('value', 'key')
        ]);
    }
}
