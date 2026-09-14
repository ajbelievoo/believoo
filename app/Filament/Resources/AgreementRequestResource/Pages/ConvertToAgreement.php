<?php

namespace App\Filament\Resources\AgreementRequestResource\Pages;

use App\Filament\Resources\AgreementRequestResource;
use App\Models\Agreement;
use App\Models\AgreementRequest;
use App\Models\AgreementHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ConvertToAgreement extends Page
{
    protected static string $resource = AgreementRequestResource::class;

    protected static string $view = 'filament.resources.agreement-request-resource.pages.convert-to-agreement';

    public AgreementRequest $record;
    public ?Agreement $agreement = null;

    // Form fields
    public $agreement_title;
    public $project_overview;
    public $technical_specs;
    public $total_amount;
    public $upfront_amount;
    public $timeline_months;
    public $start_date;
    public $payment_terms;
    public $deliverables;
    public $support_terms;
    public $work_items = [];
    public $milestones = [];

    public function mount(): void
    {
        // Pre-fill data from the request
        $this->agreement_title = 'Software Development Agreement - ' . $this->record->project_name;
        $this->project_overview = $this->record->project_description . "\n\nClient Requirements:\n" . $this->record->requirements;
        $this->technical_specs = "Framework: React Native (iOS & Android)\nAuthentication: JWT / OAuth\nPush Notifications: Firebase Cloud Messaging (FCM)\nCloud Storage: AWS S3 / Cloudinary\nDeployment: App Store (iOS) & Play Store (Android)";
        $this->total_amount = 0;
        $this->upfront_amount = 0;
        $this->timeline_months = 4;
        $this->start_date = now()->addWeek()->format('Y-m-d');
        $this->payment_terms = "Upfront Deposit: An initial commitment fee is required to initiate the project.\n\nMonthly Payments: Remaining monthly balances are due upon milestone completion.";
        $this->deliverables = "Fully functional iOS & Android Applications.\nComplete Source Code with Documentation.\nApp Store & Play Store Optimization (ASO).\n90 Days of complimentary post-launch technical support.";
        $this->support_terms = "90 Days of complimentary post-launch technical support.\nPriority email support during business hours.\nBug fixes and minor updates included.";

        // Default work items
        $this->work_items = [
            ['item_name' => 'Centralized Admin Panel & API Integration', 'description' => 'Backend development and API creation', 'amount' => 2500, 'timeline_days' => 30],
            ['item_name' => 'Android Application Development', 'description' => 'Native-standard Android app development', 'amount' => 4500, 'timeline_days' => 60],
            ['item_name' => 'iOS Application Development', 'description' => 'Native-standard iOS app development', 'amount' => 5000, 'timeline_days' => 60],
        ];

        // Default milestones
        $this->milestones = [
            ['phase_name' => 'Phase 1: UI/UX Design, Admin Setup, & Wireframes', 'description' => 'Design and planning phase', 'payment_amount' => 3000, 'timeline_month' => 1],
            ['phase_name' => 'Phase 2: Android Core Features & API Syncing', 'description' => 'Android development', 'payment_amount' => 3000, 'timeline_month' => 2],
            ['phase_name' => 'Phase 3: iOS Development, Chat System, & AI Modules', 'description' => 'iOS development', 'payment_amount' => 3000, 'timeline_month' => 3],
            ['phase_name' => 'Phase 4: Final QA, Store Submission, & Handover', 'description' => 'Testing and deployment', 'payment_amount' => 3000, 'timeline_month' => 4],
        ];

        // Calculate total from work items
        $this->calculateTotal();
    }

    public function calculateTotal(): void
    {
        $this->total_amount = collect($this->work_items)->sum('amount');
        $this->upfront_amount = round($this->total_amount * 0.25); // 25% upfront
    }

    public function updatedWorkItems(): void
    {
        $this->calculateTotal();
    }

    public function addWorkItem(): void
    {
        $this->work_items[] = ['item_name' => '', 'description' => '', 'amount' => 0, 'timeline_days' => 0];
    }

    public function removeWorkItem($index): void
    {
        unset($this->work_items[$index]);
        $this->work_items = array_values($this->work_items);
        $this->calculateTotal();
    }

    public function addMilestone(): void
    {
        $this->milestones[] = ['phase_name' => '', 'description' => '', 'payment_amount' => 0, 'timeline_month' => 1];
    }

    public function removeMilestone($index): void
    {
        unset($this->milestones[$index]);
        $this->milestones = array_values($this->milestones);
    }

    public function createAgreement(): void
    {
        // Validate
        $this->validate([
            'agreement_title' => 'required|min:5',
            'project_overview' => 'required|min:20',
            'total_amount' => 'required|numeric|min:1',
            'timeline_months' => 'required|numeric|min:1',
            'work_items' => 'required|array|min:1',
            'work_items.*.item_name' => 'required|string',
            'work_items.*.amount' => 'required|numeric|min:0',
            'milestones' => 'required|array|min:1',
            'milestones.*.phase_name' => 'required|string',
            'milestones.*.payment_amount' => 'required|numeric|min:0',
        ]);

        // Create Agreement
        $agreement = Agreement::create([
            'client_id' => $this->record->client_id,
            'title' => $this->agreement_title,
            'service_provider_name' => 'Believoo',
            'lead_developer' => 'AJ (Founder, Believoo)',
            'client_name' => $this->record->client->name,
            'project_name' => $this->record->project_name,
            'project_overview' => $this->project_overview,
            'technical_specs' => $this->technical_specs,
            'total_amount' => $this->total_amount,
            'currency' => 'USD',
            'timeline_months' => $this->timeline_months,
            'start_date' => $this->start_date,
            'upfront_amount' => $this->upfront_amount,
            'payment_terms' => $this->payment_terms,
            'deliverables' => $this->deliverables,
            'support_terms' => $this->support_terms,
            'status' => 'draft',
        ]);

        // Create Work Items
        foreach ($this->work_items as $index => $item) {
            $agreement->workItems()->create([
                'item_name' => $item['item_name'],
                'description' => $item['description'] ?? '',
                'amount' => $item['amount'] ?? 0,
                'timeline_days' => $item['timeline_days'] ?? 0,
                'sort_order' => $index,
            ]);
        }

        // Create Milestones
        foreach ($this->milestones as $index => $milestone) {
            $agreement->milestones()->create([
                'phase_name' => $milestone['phase_name'],
                'description' => $milestone['description'] ?? '',
                'payment_amount' => $milestone['payment_amount'] ?? 0,
                'timeline_month' => $milestone['timeline_month'] ?? 1,
                'status' => 'pending',
                'sort_order' => $index,
            ]);
        }

        // Create History
        AgreementHistory::create([
            'agreement_id' => $agreement->id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'description' => 'Agreement created from client request #' . $this->record->request_number,
        ]);

        // Update Request
        $this->record->update([
            'status' => 'converted',
            'agreement_id' => $agreement->id,
            'converted_at' => now(),
        ]);

        // Store agreement for view
        $this->agreement = $agreement;

        // Notify client
        $this->record->client->notify(new \App\Notifications\AgreementCreatedFromRequestNotification($agreement, $this->record));

        Notification::make()
            ->title('Agreement Created Successfully')
            ->body('The agreement has been created and linked to the client request. You can now send it to the client.')
            ->success()
            ->send();

        // Redirect to agreement edit page
        $this->redirect(route('filament.admin.resources.agreements.edit', $agreement));
    }

    public function getTitle(): string
    {
        return 'Convert Request to Agreement - ' . $this->record->request_number;
    }

    public function getBreadcrumbs(): array
    {
        return [
            AgreementRequestResource::getUrl() => 'Agreement Requests',
            AgreementRequestResource::getUrl('view', ['record' => $this->record]) => $this->record->request_number,
            '#' => 'Convert to Agreement',
        ];
    }
}
