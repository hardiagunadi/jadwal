<?php

namespace App\Console\Commands;

use App\Jobs\PublishInstagramPost;
use App\Models\InstagramPost;
use Illuminate\Console\Command;

class DispatchScheduledInstagramPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instagram:dispatch-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch publishing jobs for scheduled Instagram posts that are due.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = 0;

        InstagramPost::scheduledAndDue()
            ->lazyById()
            ->each(function (InstagramPost $post) use (&$count): void {
                PublishInstagramPost::dispatch($post);
                $count++;
            });

        $this->info(sprintf('Dispatched %d Instagram post(s) for publishing.', $count));

        return self::SUCCESS;
    }
}
