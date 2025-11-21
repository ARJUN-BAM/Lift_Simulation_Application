<?php

namespace App\Services;

use App\Models\Podcast;
use App\Services\AudioProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessPodcast implements ShouldQueue
{

    /**
     * Create a new job instance. 
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        dump('Hello World!');
    }
}
