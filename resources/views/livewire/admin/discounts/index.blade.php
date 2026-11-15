<div class="space-y-6">
    <x-admin.event-nav :event="$event" />

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold">Discount codes</h2>
            <p class="text-sm text-slate-500">{{ $event->title }}</p>
        </div>

        <button type="button" wire:click="openCreate"
                class="rounded-md bg-slate-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-700">
            New discount code
        </button>
    </div>

    @if ($showForm)
        <x-ui.card :title="$editingId ? 'Edit discount code' : 'New discount code'">
            <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="discount-code" class="mb-1 block text-sm font-medium text-slate-700">Code</label>
                    <input id="discount-code" type="text" wire:model="code" autocomplete="off"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm uppercase focus:border-slate-500 focus:outline-none">
                    @error('code') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="discount-type" class="mb-1 block text-sm font-medium text-slate-700">Type</label>
                    <select id="discount-type" wire:model.live="type"
                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @foreach ($types as $discountType)
                            <option value="{{ $discountType->value }}">{{ $discountType->label() }}</option>
                        @endforeach
                    </select>
                    @error('type') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="discount-value" class="mb-1 block text-sm font-medium text-slate-700">
                        {{ $type === 'percentage' ? 'Percentage (1-100)' : 'Amount ('.$event->currency_code.')' }}
                    </label>
                    <input id="discount-value" type="number" step="{{ $type === 'percentage' ? '1' : '0.01' }}"
                           min="{{ $type === 'percentage' ? '1' : '0.01' }}" wire:model="value"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    @error('value') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="discount-quantity" class="mb-1 block text-sm font-medium text-slate-700">
                        Quantity available <span class="font-normal text-slate-400">(blank = unlimited)</span>
                    </label>
                    <input id="discount-quantity" type="number" min="1" wire:model="quantity_available"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    @error('quantity_available') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="discount-min-quantity" class="mb-1 block text-sm font-medium text-slate-700">Minimum quantity</label>
                    <input id="discount-min-quantity" type="number" min="1" wire:model="min_quantity"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    @error('min_quantity') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="discount-expires" class="mb-1 block text-sm font-medium text-slate-700">Expires at</label>
                    <input id="discount-expires" type="datetime-local" wire:model="expires_at"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    @error('expires_at') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-2 sm:col-span-2">
                    <input id="discount-active" type="checkbox" wire:model="is_active"
                           class="size-4 rounded border-slate-300">
                    <label for="discount-active" class="text-sm text-slate-700">Active</label>
                    @error('is_active') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-2 sm:col-span-2">
                    <button type="submit"
                            class="rounded-md bg-slate-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-700">
                        {{ $editingId ? 'Save changes' : 'Create code' }}
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
        @if ($discountCodes->isEmpty())
            <div class="py-10 text-center">
                <p class="text-sm font-medium text-slate-900">No discount codes yet</p>
                <p class="mt-1 text-sm text-slate-500">Create a code to offer percentage or fixed discounts at checkout.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="py-2 pr-4">Code</th>
                            <th class="py-2 pr-4">Type</th>
                            <th class="py-2 pr-4">Value</th>
                            <th class="py-2 pr-4">Used / available</th>
                            <th class="py-2 pr-4">Min qty</th>
                            <th class="py-2 pr-4">Expires</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($discountCodes as $discountCode)
                            <tr wire:key="discount-code-{{ $discountCode->id }}">
                                <td class="py-3 pr-4 font-mono text-sm font-medium">{{ $discountCode->code }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $discountCode->type->label() }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $this->formatValue($discountCode) }}</td>
                                <td class="py-3 pr-4 text-slate-600">
                                    {{ $discountCode->quantity_used }} / {{ $discountCode->quantity_available ?? '∞' }}
                                </td>
                                <td class="py-3 pr-4 text-slate-600">{{ $discountCode->min_quantity }}</td>
                                <td class="py-3 pr-4 text-slate-600">
                                    {{ $discountCode->expires_at?->format('d M Y H:i') ?? '—' }}
                                </td>
                                <td class="py-3 pr-4">
                                    @if ($discountCode->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Active</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="py-3">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <button type="button" wire:click="openEdit({{ $discountCode->id }})"
                                                class="rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-100">
                                            Edit
                                        </button>
                                        <button type="button" wire:click="toggleActive({{ $discountCode->id }})"
                                                class="rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-100">
                                            {{ $discountCode->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                        <button type="button" wire:click="delete({{ $discountCode->id }})"
                                                wire:confirm="Delete the discount code {{ $discountCode->code }}?"
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
