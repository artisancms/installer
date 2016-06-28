<?php

namespace ArtisanCms\Installer;

use ZipArchive;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    public function configure()
    {
        $this->setName('install')
             ->setDescription('Complete the installation process');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $file = getcwd() .  "/config/app.php";
        $file_contents = file($file);

        //the blank line is needed for consistency

        $service_providers = <<<SERVICE_PROVIDERS

        ArtisanCMS\CMS\Providers\CMSServiceProvider::class,

SERVICE_PROVIDERS;
        // the blank line is needed for consistency
        $aliases = <<<ALIASES

        // Nothing to add yet.

ALIASES;
    
        $temp_file = fopen(getcwd() . "/config/app.temp.php", "w");
        $write_the_providers = false;
        $write_the_aliases = false;
        foreach ($file_contents as $line) {
            // We found the providers array declaration
            // Now write the service providers
            if ($write_the_providers === true) {
                fwrite($temp_file, $service_providers);
                $write_the_providers = false; // done writing the service providers
                continue;
            }
            // We found the aliases array, now write the aliases
            if ($write_the_aliases === true) {
                fwrite($temp_file, $aliases);
                $write_the_aliases = false;
                continue;
            }
            //look for the declaration of the providers array
            if (strpos($line, "'providers' => [") !== false) {
            // on the next line, we need to write the service providers lines
                $write_the_providers = true;
            }
            //look for the declaration of the aliases array
            if (strpos($line, "'aliases' => [") !== false) {
            //on the next line, we need to write the aliases lines
                $write_the_aliases = true;

            }
            //else continue writing the file as it is
            fwrite($temp_file, $line);
        }
        fclose($temp_file);
        unlink($file);
        rename(getcwd() . '/config/app.temp.php', getcwd() . "/config/app.php");
    }
}
