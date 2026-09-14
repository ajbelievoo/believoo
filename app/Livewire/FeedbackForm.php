<?php

namespace App\Livewire;

use App\Models\Testimonial;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class FeedbackForm extends Component
{
    public string $name = '';
    public string $title = '';
    public string $company = '';
    public int $rating = 5;
    public string $content = '';
    public string $website = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:80',
            'title' => 'nullable|string|max:100',
            'company' => 'nullable|string|max:100',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string|min:10|max:1000',
            'website' => 'nullable|string',
        ];
    }

    public function setRating(int $value): void
    {
        $this->rating = max(1, min(5, $value));
    }

    public function submit()
    {
        $this->validate();

        $success = 'Thank you for your feedback! It will appear on the site after a quick review.';

        if ($this->website !== '') {
            session()->flash('feedback_success', $success);
            $this->reset(['name', 'title', 'company', 'content', 'website']);
            $this->rating = 5;
            return;
        }

        $key = 'feedback-form:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('content', 'Too many submissions. Please try again in an hour.');
            return;
        }
        RateLimiter::hit($key, 3600);

        Testimonial::create([
            'name' => $this->name,
            'title' => $this->title ?: null,
            'company' => $this->company ?: null,
            'content' => $this->content,
            'rating' => $this->rating,
            'is_visible' => false,
        ]);

        session()->flash('feedback_success', $success);
        $this->reset(['name', 'title', 'company', 'content', 'website']);
        $this->rating = 5;
    }

    public function render()
    {
        return view('livewire.feedback-form');
    }
}
