<?php

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;

class PublishPage extends Command
{
    protected $signature = 'tieco:page {slug : privacy | terms | requests-policy | about} {--hide : unpublish instead}';

    protected $description = 'Publish (or hide) a content page. Only publish text a lawyer has reviewed.';

    public function handle(): int
    {
        $page = Page::where('slug', $this->argument('slug'))->first();
        if (! $page) {
            $this->error('No such page. Run: php artisan db:seed --class=PageSeeder');

            return self::FAILURE;
        }
        $page->update(['is_published' => ! $this->option('hide')]);
        $this->info($page->slug.' is now '.($page->is_published ? 'PUBLISHED' : 'hidden').'.');

        return self::SUCCESS;
    }
}
