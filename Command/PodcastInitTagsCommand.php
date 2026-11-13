<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AsCommand(name: 'podcast:init:tags', description: 'Create the Podcast publication channel if it does not exist')]
#[AutoconfigureTag('pumukit.setup')]
class PodcastInitTagsCommand extends Command
{
    private const PUBLICATION_CHANNEL_CODE = 'PUCHPODCAST';

    public function __construct(private readonly DocumentManager $documentManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Not needed any more: the command only creates the tag if it does not exist');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repository = $this->documentManager->getRepository(Tag::class);
        if ($repository->findOneBy(['cod' => self::PUBLICATION_CHANNEL_CODE])) {
            $output->writeln(sprintf('The tag %s already exists.', self::PUBLICATION_CHANNEL_CODE));

            return Command::SUCCESS;
        }

        $parent = $repository->findOneBy(['cod' => 'PUBCHANNELS']);
        if (!$parent instanceof Tag) {
            $output->writeln('<error>There is no tag PUBCHANNELS to be the parent of the Podcast publication channel: run pumukit:init:repo first.</error>');

            return Command::FAILURE;
        }

        $tag = new Tag();
        $tag->setCod(self::PUBLICATION_CHANNEL_CODE);
        $tag->setMetatag(false);
        $tag->setDisplay(true);
        foreach (['es', 'gl', 'en'] as $locale) {
            $tag->setTitle('Podcast', $locale);
        }
        $tag->setParent($parent);
        $tag->setProperty('modal_path', 'pumukitpodcast_modal_index');
        $this->documentManager->persist($tag);
        $this->documentManager->flush();

        $output->writeln(sprintf('The tag %s is created.', self::PUBLICATION_CHANNEL_CODE));

        return Command::SUCCESS;
    }
}
