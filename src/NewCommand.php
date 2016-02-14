<?php

namespace ArtisanCms;

use ZipArchive;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewCommand extends Command
{
    public function configure()
    {
        $this->setName('new')
             ->setDescription('Create a new install of ArtisanCMS')
             ->addArgument('name', InputArgument::REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $directory = getcwd() . '/' . $input->getArgument('name');

        $output->writeln('<info>Preparing Studio...</info>');

        $this->assertApplicationDoesNotExist($directory, $output);

        $this->download($zipFile = $this->makeFileName())
             ->extract($zipFile, $directory)
             ->cleanUp($zipFile);

        $composer = $this->findComposer();

        $commands = [
            $composer.' require artisancms/core --no-update',
            $composer.' install --no-scripts',
            $composer.' run-script post-root-package-install',
            $composer.' run-script post-install-cmd',
            $composer.' run-script post-create-project-cmd',

        ];

        $process = new Process(implode(' && ', $commands), $directory, null, null, null);

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        $output->writeln('<comment>Studio ready, go create!</comment>');
    }

    private function assertApplicationDoesNotExist($directory, OutputInterface $output)
    {
        if (is_dir($directory)) {
            $output->writeln('<error>Application already exists.</error>');
            exit(1);
        }
    }

    private function download($zipFile)
    {
        $response = (new Client)->get('http://cabinet.laravel.com/latest.zip')->getBody();

        file_put_contents($zipFile, $response);

        return $this;
    }

    private function makeFileName()
    {
        return getcwd() . '/laravel_' . md5(uniqid()) . '.zip';
    }

    public function extract($zipFile, $directory)
    {
        $archive = new ZipArchive;

        $archive->open($zipFile);

        $archive->extractTo($directory);

        $archive->close();

        return $this;
    }

    private function cleanUp($zipFile)
    {
        @chmod($zipFile, 0777);
        @unlink($zipFile);

        return $this;
    }

    protected function findComposer()
    {
        if (file_exists(getcwd().'/composer.phar')) {
            return '"'.PHP_BINARY.'" composer.phar';
        }
        return 'composer';
    }
}
