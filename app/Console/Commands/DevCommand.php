<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DevCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run development commands in just one terminal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // list command
        $server = new Process(['php', 'artisan', 'serve']);
        $queue = new Process(['php', 'artisan', 'queue:work']);

        $server->setTimeout(null);
        $queue->setTimeout(null);

        //execvute
        $server->start();
        $queue->start();

        while ($server->isRunning() || $queue->isRunning()) {
            if ($serverOutput = $server->getIncrementalOutput()) {
                $this->output->write('<fg=cyan>[SERVE]</fg=cyan> ' . $serverOutput);
            }

            if ($queueOutput = $queue->getIncrementalOutput()) {
                $this->output->write('<fg=yellow>[QUEUE]</fg=yellow> ' . $queueOutput);
            }

            usleep(100000);
        }

        return Command::SUCCESS;
    }
}
