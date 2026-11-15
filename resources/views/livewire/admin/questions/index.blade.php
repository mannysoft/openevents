<div class="space-y-6">
    <x-admin.event-nav :event="$event" />

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold">Registration questions</h2>
            <p class="text-sm text-slate-500">{{ $event->title }}</p>
        </div>

        <button type="button" wire:click="openCreate"
                class="rounded-md bg-slate-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-700">
            New question
        </button>
    </div>

    @if ($showForm)
        <x-ui.card :title="$editingId ? 'Edit question' : 'New question'">
            <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="question-title" class="mb-1 block text-sm font-medium text-slate-700">Title</label>
                    <input id="question-title" type="text" wire:model="title"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    @error('title') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="question-type" class="mb-1 block text-sm font-medium text-slate-700">Type</label>
                    <select id="question-type" wire:model.live="type"
                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @foreach ($types as $questionType)
                            <option value="{{ $questionType->value }}">{{ $questionType->label() }}</option>
                        @endforeach
                    </select>
                    @error('type') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="question-applies-to" class="mb-1 block text-sm font-medium text-slate-700">Applies to</label>
                    <select id="question-applies-to" wire:model="applies_to"
                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        <option value="attendee">Attendee</option>
                        <option value="order">Order</option>
                    </select>
                    @error('applies_to') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                @if ($type === 'select' || $type === 'checkbox')
                    <div class="sm:col-span-2">
                        <label for="question-options" class="mb-1 block text-sm font-medium text-slate-700">
                            Options <span class="font-normal text-slate-400">(comma separated)</span>
                        </label>
                        <input id="question-options" type="text" wire:model="options" placeholder="Small, Medium, Large"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('options') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label for="question-sort-order" class="mb-1 block text-sm font-medium text-slate-700">Sort order</label>
                    <input id="question-sort-order" type="number" min="0" wire:model="sort_order"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    @error('sort_order') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-2 self-end">
                    <input id="question-required" type="checkbox" wire:model="is_required"
                           class="size-4 rounded border-slate-300">
                    <label for="question-required" class="text-sm text-slate-700">Required</label>
                    @error('is_required') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-2 sm:col-span-2">
                    <button type="submit"
                            class="rounded-md bg-slate-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-700">
                        {{ $editingId ? 'Save changes' : 'Create question' }}
                    </button>
                    <button type="button" wire:click="cancel"
                            class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-100">
                        Cancel
                    </button>
                </div>
            </form>
        </x-ui.card>
    @endif

    <x-ui.card>
        @if ($questions->isEmpty())
            <div class="py-10 text-center">
                <p class="text-sm font-medium text-slate-900">No questions yet</p>
                <p class="mt-1 text-sm text-slate-500">Add questions to collect extra details during checkout.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="py-2 pr-4">Title</th>
                            <th class="py-2 pr-4">Type</th>
                            <th class="py-2 pr-4">Options</th>
                            <th class="py-2 pr-4">Required</th>
                            <th class="py-2 pr-4">Applies to</th>
                            <th class="py-2 pr-4">Answers</th>
                            <th class="py-2 pr-4">Order</th>
                            <th class="py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($questions as $question)
                            <tr wire:key="question-{{ $question->id }}">
                                <td class="py-3 pr-4 font-medium">{{ $question->title }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $question->type->label() }}</td>
                                <td class="py-3 pr-4 text-slate-600">
                                    {{ $question->options ? implode(', ', $question->options) : '—' }}
                                </td>
                                <td class="py-3 pr-4">
                                    @if ($question->is_required)
                                        <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Required</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Optional</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 text-slate-600">{{ ucfirst($question->applies_to) }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $question->answers_count }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $question->sort_order }}</td>
                                <td class="py-3">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <button type="button" wire:click="move({{ $question->id }}, 'up')"
                                                @disabled($loop->first)
                                                class="rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40">
                                            ↑
                                        </button>
                                        <button type="button" wire:click="move({{ $question->id }}, 'down')"
                                                @disabled($loop->last)
                                                class="rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40">
                                            ↓
                                        </button>
                                        <button type="button" wire:click="openEdit({{ $question->id }})"
                                                class="rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-100">
                                            Edit
                                        </button>
                                        <button type="button" wire:click="delete({{ $question->id }})"
                                                wire:confirm="Delete the question &quot;{{ $question->title }}&quot;?"
                                                class="rounded-md border border-rose-200 px-2 py-1 text-xs text-rose-700 hover:bg-rose-50">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</div>
