<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Client Request Info -->
        <div class="lg:col-span-1 space-y-6">
            <x-filament::section>
                <x-slot name="heading">Client Request Details</x-slot>
                
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-500">Request Number</p>
                        <p class="font-medium">{{ $this->record->request_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Client</p>
                        <p class="font-medium">{{ $this->record->client->name }}</p>
                        <p class="text-sm text-gray-400">{{ $this->record->client->email }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Project Name</p>
                        <p class="font-medium">{{ $this->record->project_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Budget Range</p>
                        <p class="font-medium">{{ $this->record->budget_range ?? 'Not specified' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Timeline Expectation</p>
                        <p class="font-medium">{{ $this->record->timeline_expectation ?? 'Not specified' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Preferred Technology</p>
                        <p class="font-medium">{{ $this->record->preferred_technology ?? 'Not specified' }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Project Description</x-slot>
                <div class="prose prose-sm max-w-none">
                    {!! $this->record->project_description !!}
                </div>
            </x-filament::section>

            @if($this->record->requirements)
            <x-filament::section>
                <x-slot name="heading">Specific Requirements</x-slot>
                <div class="prose prose-sm max-w-none">
                    {!! nl2br(e($this->record->requirements)) !!}
                </div>
            </x-filament::section>
            @endif
        </div>

        <!-- Right Column: Agreement Form -->
        <div class="lg:col-span-2 space-y-6">
            <form wire:submit="createAgreement" class="space-y-6">
                {{ $this->form }}

                <!-- Agreement Title -->
                <x-filament::section>
                    <x-slot name="heading">Agreement Title</x-slot>
                    <input type="text" wire:model="agreement_title" 
                        class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                        placeholder="e.g., Software Development Agreement - Project Name">
                    @error('agreement_title') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </x-filament::section>

                <!-- Project Overview -->
                <x-filament::section>
                    <x-slot name="heading">Project Overview & Scope</x-slot>
                    <textarea wire:model="project_overview" rows="6"
                        class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                        placeholder="Detailed project description and scope..."></textarea>
                    @error('project_overview') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </x-filament::section>

                <!-- Technical Specifications -->
                <x-filament::section>
                    <x-slot name="heading">Technical Specifications</x-slot>
                    <textarea wire:model="technical_specs" rows="4"
                        class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                        placeholder="Framework, authentication, deployment details..."></textarea>
                </x-filament::section>

                <!-- Work Items -->
                <x-filament::section>
                    <x-slot name="heading">Work Items & Costing</x-slot>
                    <x-slot name="headerEnd">
                        <button type="button" wire:click="addWorkItem" 
                            class="inline-flex items-center px-3 py-1 text-sm font-medium text-primary-600 bg-primary-100 rounded-lg hover:bg-primary-200">
                            <x-heroicon-m-plus class="w-4 h-4 mr-1"/>
                            Add Item
                        </button>
                    </x-slot>

                    <div class="space-y-4">
                        @foreach($work_items as $index => $item)
                        <div class="p-4 border rounded-lg bg-gray-50">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Item Name</label>
                                    <input type="text" wire:model="work_items.{{ $index }}.item_name"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                        placeholder="e.g., iOS Application Development">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <input type="text" wire:model="work_items.{{ $index }}.description"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Amount ($)</label>
                                    <input type="number" wire:model="work_items.{{ $index }}.amount" wire:change="updatedWorkItems"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Timeline (Days)</label>
                                    <input type="number" wire:model="work_items.{{ $index }}.timeline_days"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="mt-3 flex justify-end">
                                <button type="button" wire:click="removeWorkItem({{ $index }})"
                                    class="text-sm text-danger-600 hover:text-danger-800">
                                    <x-heroicon-m-trash class="w-4 h-4 inline mr-1"/>
                                    Remove
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-4 p-4 bg-primary-50 rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Total Project Value:</span>
                            <span class="text-2xl font-bold text-primary-600">${{ number_format($total_amount, 2) }}</span>
                        </div>
                    </div>
                </x-filament::section>

                <!-- Milestones -->
                <x-filament::section>
                    <x-slot name="heading">Timeline & Milestones</x-slot>
                    <x-slot name="headerEnd">
                        <button type="button" wire:click="addMilestone" 
                            class="inline-flex items-center px-3 py-1 text-sm font-medium text-primary-600 bg-primary-100 rounded-lg hover:bg-primary-200">
                            <x-heroicon-m-plus class="w-4 h-4 mr-1"/>
                            Add Milestone
                        </button>
                    </x-slot>

                    <div class="space-y-4">
                        @foreach($milestones as $index => $milestone)
                        <div class="p-4 border rounded-lg bg-gray-50">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Phase Name</label>
                                    <input type="text" wire:model="milestones.{{ $index }}.phase_name"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                        placeholder="e.g., Phase 1: UI/UX Design">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Month</label>
                                    <input type="number" wire:model="milestones.{{ $index }}.timeline_month" min="1"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Payment Amount ($)</label>
                                    <input type="number" wire:model="milestones.{{ $index }}.payment_amount"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <input type="text" wire:model="milestones.{{ $index }}.description"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="mt-3 flex justify-end">
                                <button type="button" wire:click="removeMilestone({{ $index }})"
                                    class="text-sm text-danger-600 hover:text-danger-800">
                                    <x-heroicon-m-trash class="w-4 h-4 inline mr-1"/>
                                    Remove
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </x-filament::section>

                <!-- Financial Details -->
                <x-filament::section>
                    <x-slot name="heading">Financial Details</x-slot>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Total Amount ($)</label>
                            <input type="number" wire:model="total_amount" readonly
                                class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Upfront Amount ($)</label>
                            <input type="number" wire:model="upfront_amount"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Timeline (Months)</label>
                            <input type="number" wire:model="timeline_months" min="1"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" wire:model="start_date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        </div>
                    </div>
                </x-filament::section>

                <!-- Terms -->
                <x-filament::section>
                    <x-slot name="heading">Terms & Conditions</x-slot>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Payment Terms</label>
                            <textarea wire:model="payment_terms" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Deliverables</label>
                            <textarea wire:model="deliverables" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Support Terms</label>
                            <textarea wire:model="support_terms" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                </x-filament::section>

                <!-- Submit Button -->
                <div class="flex justify-end gap-3">
                    <a href="{{ \App\Filament\Resources\AgreementRequestResource::getUrl('view', ['record' => $this->record]) }}"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                        class="inline-flex items-center px-6 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <x-heroicon-m-document-check class="w-5 h-5 mr-2"/>
                        Create Agreement
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-filament-panels::page>
