<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'crawl4ai:sitemap')]
class CrawlSitemapXmlCommand extends AbstractCrawlCommand
{
    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $startTime = time();
        $sitemapUrl = $input->getArgument('sitemapUrl');
        $outputFileNamePrefix = $input->getOption('outputFileNamePrefix');
        $locale = $input->getOption('locale');
        $fileCompression = $input->getOption('fileCompression');

        $output->writeln('Reading sitemap: ' . $sitemapUrl);
        $urls = $this->extractUrlsFromSitemap($sitemapUrl);
        $output->writeln('URLs found: ' . count($urls));


        $markdown = json_encode(
            $this->crawl(
                urls: $urls,
                locale: $locale
            ),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        $this->writeOutputFile($markdown, $sitemapUrl, $outputFileNamePrefix, $fileCompression);

        $output->writeln('Process duration: ' . (time() - $startTime) . 's');
        
        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('sitemapUrl', InputArgument::REQUIRED)
            ->addOption('outputFileNamePrefix', null, InputOption::VALUE_OPTIONAL, 'Prefix for the output file name', 'crawl')
            ->addOption('locale', null, InputOption::VALUE_OPTIONAL, 'Locale for the crawl', 'en-EN')
            ->addOption('fileCompression', null, InputOption::VALUE_NONE, 'Compress output file with gzip');
    }

    protected function extractUrlsFromSitemap(string $sitemapUrl): array
    {
        $xml = simplexml_load_file($sitemapUrl);
        $urls = [];
        foreach ($xml->url as $url) {
            $urls[] = (string) $url->loc;
        }
        return $urls;
    }
}
