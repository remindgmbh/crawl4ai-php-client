<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'crawl4ai:sitemap')]
class CrawlSitemapXmlCommand extends AbstractCrawlCommand
{
    public function __invoke(
        #[Argument('The URL of the sitemap.')] string $sitemapUrl,
        OutputInterface $output
    ): int {
        $output->writeln('Reading sitemap: ' . $sitemapUrl);
        $urls = $this->extractUrlsFromSitemap($sitemapUrl);
        $output->writeln('URLs found: ' . count($urls));

        foreach ($urls as $url) {
            $output->writeln('Crawling URL: ' . $url);
            // $this->crawl($url);
        }
        
        return Command::SUCCESS;
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
