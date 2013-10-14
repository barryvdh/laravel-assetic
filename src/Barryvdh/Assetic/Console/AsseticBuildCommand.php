<?php

namespace Barryvdh\Assetic\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class AsseticBuildCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'assetic:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build assets with Assetic, from views';

    /**
     * Create a new command instance.
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
     * @return void
     */
    public function fire()
    {

        $this->info('Starting Assetic building..');
        $app = $this->laravel;

        // Boot assetic
//        $assetic = $app['assetic'];

        $helper = $app['assetic.dumper'];
        if (isset($app['twig'])) {
            $helper->addTwigAssets();
        }
        $helper->dumpAssets();

        $this->info('Done building assets!');

    }



    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array();
    }

}