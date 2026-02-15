<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PruneOrphanedTagsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tags:prune-orphaned';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete tags that are not associated with any recipes';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $deletedCount = DB::table('tags')
            ->whereNotExists(function (Builder $query) {
                $query->select(DB::raw(1))
                    ->from('recipe_tag')
                    ->whereColumn('recipe_tag.tag_id', 'tags.id');
            })
            ->delete();

        $this->info("Deleted {$deletedCount} orphaned tag(s).");
    }
}
