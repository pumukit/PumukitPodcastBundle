<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class PodcastInitItunesUTagsCommand extends Command
{
    private $documentManager;
    private $tagsPath = '../Resources/data/tags/';

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('pumukit:podcast:init:itunesu')
            ->setDescription('Load podcast itunesu tag data fixture to your database')
            ->addArgument('file', InputArgument::OPTIONAL, 'Input CSV path')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(
                <<<'EOT'
Command to load a controlled Podcast ItunesU tags data into a database. Useful for init Podcast environment.

The --force parameter has to be used to actually drop the database.

EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('force')) {
            return $this->executeTags($input, $output);
        }
        $output->writeln('<error>ATTENTION:</error> This operation should not be executed in a production environment without backup.');
        $output->writeln('');
        $output->writeln('Please run the operation with --force to execute.');

        return -1;
    }

    protected function executeTags(InputInterface $input, OutputInterface $output): int
    {
        $finder = new Finder();
        $finder->files()->in(__DIR__.'/'.$this->tagsPath);
        $file = $input->getArgument('file');
        if ((0 == strcmp($file, '')) && (!$finder)) {
            $output->writeln("<error>Tags: There's no data to initialize</error>");

            return -1;
        }

        $root = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => 'ROOT']);
        if (null === $root) {
            $output->writeln("<error>Tags: There's no ROOT tag. Please exec pumukit:init:repo tag</error>");

            return -1;
        }

        if ($file) {
            $this->createFromFile($file, $root, $output, 'tag');
        } else {
            foreach ($finder as $tagFile) {
                $this->createFromFile($tagFile, $root, $output, 'tag');
            }
        }

        return 0;
    }

    protected function createFromFile($file, $root, OutputInterface $output, $repoName)
    {
        if (!file_exists($file)) {
            $output->writeln('<error>'.$repoName.': Error stating '.$file.'</error>');

            return -1;
        }

        $idCodMapping = [];

        $row = 1;
        if (false !== ($file = fopen($file, 'r'))) {
            while (false !== ($currentRow = fgetcsv($file, 0, ';'))) {
                $number = count($currentRow);
                if (('tag' === $repoName) && (6 == $number || 9 == $number)) {
                    if ('id' == trim($currentRow[0])) {
                        continue;
                    }
                    $parent = $idCodMapping[$currentRow[2]] ?? $root;

                    try {
                        $tag = $this->createTagFromCsvArray($currentRow, $parent);
                        $idCodMapping[$currentRow[0]] = $tag;
                        $output->writeln('Tag persisted - new id: '.$tag->getId().' cod: '.$tag->getCod());
                    } catch (\Exception $e) {
                        $output->writeln('<error>'.$repoName.': '.$e->getMessage().'</error>');
                    }
                } else {
                    $output->writeln($repoName.': Last valid row = ...');
                    $output->writeln("Error: line {$row} has {$number} elements");
                }

                if (0 == $row % 100) {
                    echo 'Row '.$row."\n";
                }

                ++$row;
            }
            fclose($file);
            $this->documentManager->flush();
        } else {
            $output->writeln('<error>Error opening '.$file.'</error>');

            return -1;
        }
    }

    private function createTagFromCsvArray($csv_array, $tag_parent = null): Tag
    {
        if ($tag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $csv_array[1]])) {
            throw new \LengthException('Nothing done - Tag retrieved from DB id: '.$tag->getId().' cod: '.$tag->getCod());
        }

        $tag = new Tag();
        $tag->setCod($csv_array[1]);
        $tag->setMetatag($csv_array[3]);
        $tag->setDisplay($csv_array[4]);
        if ($tag_parent) {
            $tag->setParent($tag_parent);
        }
        // NOTE Take care of csv language order!
        if (isset($csv_array[5])) {
            $tag->setTitle($csv_array[5], 'en');
        }
        if (isset($csv_array[6])) {
            $tag->setTitle($csv_array[6], 'es');
        }
        if (isset($csv_array[7])) {
            $tag->setTitle($csv_array[7], 'gl');
        }
        if (isset($csv_array[8])) {
            $tag->setTitle($csv_array[8], 'de');
        }

        $this->documentManager->persist($tag);

        return $tag;
    }
}
