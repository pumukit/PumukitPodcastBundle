<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PodcastInitTagsCommand extends Command
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('podcast:init:tags')
            ->setDescription('Load podcast tag data fixture to your database')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(
                <<<'EOT'
Command to load a controlled Podcast tags data into a database. Useful for init Podcast environment.

The --force parameter has to be used to actually drop the database.

EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('force')) {
            $podcastPublicationChannelTag = $this->createTagWithCode('PUCHPODCAST', 'PodcastEDU/iTunesU', 'PUBCHANNELS', false);
            $podcastPublicationChannelTag->setProperty('modal_path', 'pumukitpodcast_modal_index');
            $this->documentManager->persist($podcastPublicationChannelTag);
            $this->documentManager->flush();

            $output->writeln('Tag persisted - new id: '.$podcastPublicationChannelTag->getId().' cod: '.$podcastPublicationChannelTag->getCod());
        } else {
            $output->writeln('<error>ATTENTION:</error> This operation should not be executed in a production environment without backup.');
            $output->writeln('');
            $output->writeln('Please run the operation with --force to execute.');

            return -1;
        }

        return 0;
    }

    private function createTagWithCode(string $code, string $title, string $tagParentCode = null, bool $metatag = false): Tag
    {
        if ($tag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $code])) {
            throw new \Exception('Nothing done - Tag retrieved from DB id: '.$tag->getId().' cod: '.$tag->getCod());
        }
        $tag = new Tag();
        $tag->setCod($code);
        $tag->setMetatag($metatag);
        $tag->setDisplay(true);
        $tag->setTitle($title, 'es');
        $tag->setTitle($title, 'gl');
        $tag->setTitle($title, 'en');
        if ($tagParentCode) {
            if ($parent = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $tagParentCode])) {
                $tag->setParent($parent);
            } else {
                throw new \Exception('Nothing done - There is no tag in the database with code '.$tagParentCode.' to be the parent tag');
            }
        }
        $this->documentManager->persist($tag);
        $this->documentManager->flush();

        return $tag;
    }
}
