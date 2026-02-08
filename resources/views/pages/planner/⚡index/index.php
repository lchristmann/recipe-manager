<?php

use App\Models\PlannedRecipe;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url(keep:true)]
    public string $week;

    public CarbonImmutable $weekStart;

    public array $plans = [];

    public int $createRowVersion = 0;

    // ---------------- lifecycle ----------------

    public function mount(): void
    {
        $this->weekStart = isset($this->week)
            ? CarbonImmutable::parse($this->week)->startOfWeek()
            : now()->toImmutable()->startOfWeek();

        $this->week = $this->weekStart->toDateString();

        $this->loadWeek();
    }

    // ---------------- week navigation ----------------

    public function previousWeek(): void
    {
        $this->weekStart = $this->weekStart->subWeek();
        $this->week = $this->weekStart->toDateString();
        $this->loadWeek();
    }

    public function nextWeek(): void
    {
        $this->weekStart = $this->weekStart->addWeek();
        $this->week = $this->weekStart->toDateString();
        $this->loadWeek();
    }

    public function goToCurrentWeek(): void
    {
        $this->weekStart = now()->toImmutable()->startOfWeek();
        $this->week = $this->weekStart->toDateString();
        $this->loadWeek();
    }

    // ---------------- loading ----------------

    protected function loadWeek(): void
    {
        $this->plans = [];

        foreach ($this->days as $day) {
            $this->plans[$day] = [];
        }

        $this->loadExistingPlans();
    }

    protected function loadExistingPlans(): void
    {
        PlannedRecipe::query()
            ->where('user_id', auth()->id())
            ->whereBetween('date', [
                $this->weekStart,
                $this->weekStart->addDays(6),
            ])
            ->with('recipe')
            ->orderBy('position')
            ->get()
            ->groupBy(fn ($p) => $p->date->toDateString())
            ->each(function ($items, $date) {
                foreach ($items as $item) {
                    $this->plans[$date][] = [
                        'uuid' => (string) str()->uuid(),
                        'id' => $item->id,
                        'recipe_id' => $item->recipe_id,
                        'recipe_name' => $item->recipe->title,
                        'mode' => 'view',
                    ];
                }
            });
    }

    // ---------------- computed ----------------

    #[Computed]
    public function days(): array
    {
        return collect(range(0, 6))
            ->map(fn ($i) => $this->weekStart->addDays($i)->toDateString())
            ->all();
    }

    // ---------------- refresh ----------------

    #[On('planner-refresh')]
    public function refreshPlanner(): void
    {
        $this->loadWeek();
        $this->createRowVersion++;
    }
};
