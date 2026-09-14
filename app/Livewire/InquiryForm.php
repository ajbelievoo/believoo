<?php

namespace App\Livewire;

use Livewire\Component;

use App\Models\Lead;

class InquiryForm extends Component
{
    public int $step = 1;
    public int $totalSteps = 3;

    // Step 1
    public $full_name;
    public $email;
    public $phone;
    public $company;

    // Step 2
    public $project_type;
    public $budget;

    // Step 3
    public $requirements;

    protected function rules()
    {
        if ($this->step == 1) {
            return [
                'full_name' => 'required|min:3',
                'email' => 'required|email',
                'phone' => 'required',
                'company' => 'nullable',
            ];
        } elseif ($this->step == 2) {
            return [
                'project_type' => 'required',
                'budget' => 'required',
            ];
        }

        return [
            'requirements' => 'required|min:10',
        ];
    }

    public function nextStep()
    {
        try {
            $this->validate();
            $this->step++;
            $this->dispatch('scroll-to-top');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('validation-failed');
            throw $e;
        }
    }

    public function prevStep()
    {
        $this->step--;
    }

    public function submit()
    {
        try {
            $this->validate();

            Lead::create([
                'full_name' => $this->full_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'company' => $this->company,
                'project_type' => $this->project_type,
                'budget' => $this->budget,
                'requirements' => $this->requirements,
                'status' => 'new',
            ]);

            $this->reset();
            $this->step = 1;
            session()->flash('inquiry_success', 'Your inquiry has been submitted successfully! We will contact you soon.');
            $this->dispatch('inquiry-submitted');
        } catch (\Exception $e) {
            $this->dispatch('validation-failed');
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
            session()->flash('error', 'Something went wrong. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.inquiry-form');
    }
}
