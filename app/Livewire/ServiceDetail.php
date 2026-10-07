<?php

namespace App\Livewire;

use App\Models\Service;
use Livewire\Component;

class ServiceDetail extends Component
{
    public Service $service;

    protected array $hostingSlugs = [
        'managed-vps-cloud',
        'vps-1', 'vps-2', 'vps-3', 'vps-4', 'vps-5', 'vps-6',
        'web-hosting-starter', 'web-hosting-business', 'web-hosting-pro',
        'streaming-addon',
    ];

    public function mount(Service $service)
    {
        if (in_array($service->slug, $this->hostingSlugs, true)) {
            return redirect('https://ghc.believoo.com');
        }
        // RAZORPAY-REVIEW: hide deactivated services from public
        if (!is_null($service->is_active) && !$service->is_active) {
            abort(404);
        }
        $this->service = $service;
    }

    public function render()
    {
        $title = $this->service->meta_title
            ?: $this->service->title . ' - Believoo ' . ($this->service->category ?? 'Services');

        $description = $this->service->meta_description
            ?: (!empty($this->service->description)
                ? \Illuminate\Support\Str::limit(strip_tags($this->service->description), 160)
                : null);

        $keywords = $this->service->meta_keywords
            ?: collect([
                $this->service->title,
                $this->service->category,
                'Believoo',
                'services',
            ])->filter()->implode(', ');

        // Build FAQ schema from service FAQs
        $faqSchema = null;
        if (!empty($this->service->faqs) && is_array($this->service->faqs)) {
            $mainEntity = array_values(array_filter(array_map(fn($faq) => [
                '@type' => 'Question',
                'name' => $faq['question'] ?? '',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer'] ?? '',
                ],
            ], $this->service->faqs), fn($item) => !empty($item['name'])));

            if (!empty($mainEntity)) {
                $faqSchema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => $mainEntity,
                ];
            }
        }

        return view('livewire.service-detail')
            ->layout('components.layouts.believoo', [
                'title' => $title,
                'description' => $description,
                'keywords' => $keywords,
                'faqSchema' => $faqSchema,
                'settings' => \App\Models\Setting::pluck('value', 'key')
            ]);
    }
}
