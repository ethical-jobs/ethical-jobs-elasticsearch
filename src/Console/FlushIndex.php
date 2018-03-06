<?php

namespace EthicalJobs\Console;

use Illuminate\Console\Command;
use Artisan;

/**
 * Deletes, creates and then indexes documents
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

class FlushIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ej:es:flush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes, creates and then indexes documents';

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->artisan('ej:es:index-delete');

        $this->artisan('ej:es:index-create');

        $this->artisan('ej:es:index');
    }
}
