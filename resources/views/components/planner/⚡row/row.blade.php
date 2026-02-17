<div class="flex items-center gap-3">

    @if($mode === 'view')

        <div class="flex-1">
            <flux:text>
                <flux:link href="{{ route('recipes.show', $recipeId) }}" wire:navigate>
                    {{ $plan['recipe_name'] }}
                </flux:link>
            </flux:text>
        </div>

        <div class="w-[80px] flex justify-end gap-1">
            <flux:button icon="pencil" variant="ghost" wire:click="startEdit" class="cursor-pointer"/>
            <flux:button icon="trash" variant="ghost" wire:click="delete" class="cursor-pointer"/>
        </div>

    @else

        <div class="flex-1">
            <flux:select wire:model="recipeId" variant="combobox" :filter="false">
                <x-slot name="input">
                    <flux:select.input wire:model.live="search" placeholder="{{ __('Search recipe...') }}"/>
                </x-slot>

                @foreach($this->recipes as $recipe)
                    <flux:select.option :value="$recipe->id">
                        {{ $recipe->title }} ({{ $recipe->cookbook->community ? __('community') : $recipe->cookbook->user->name }})
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="w-[80px] flex justify-end gap-1">
            <flux:button icon="check" variant="outline" wire:click="save" class="cursor-pointer"/>
            <flux:button icon="trash" variant="ghost" wire:click="delete" class="cursor-pointer"/>
        </div>

    @endif

</div>
