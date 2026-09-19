<?php

declare(strict_types=1);

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
        $reverb = new Process(['php', 'artisan', 'reverb:start']);

        $server->setTimeout(null);
        $queue->setTimeout(null);
        $reverb->setTimeout(null);

        // execvute
        $server->start();
        $queue->start();
        $reverb->start();

        while ($server->isRunning() || $queue->isRunning() || $reverb->isRunning()) {
            if ($serverOutput = $server->getIncrementalOutput()) {
                $this->output->write('<fg=cyan>[SERVE]</fg=cyan> '.$serverOutput);
            }

            if ($queueOutput = $queue->getIncrementalOutput()) {
                $this->output->write('<fg=yellow>[QUEUE]</fg=yellow> '.$queueOutput);
            }

            if ($reverbOutput = $reverb->getIncrementalOutput()) {
                $this->output->write('<fg=magenta>[REVERB]</fg=magenta> '.$reverbOutput);
            }

            usleep(100000);
        }

        return Command::SUCCESS;
    }
}
