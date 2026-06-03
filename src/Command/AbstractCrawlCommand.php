<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractCrawlCommand extends Command
{
    protected const BATCH_SIZE = 10;

    protected const DEFAULT_CRAWLER_CONFIG = [
        'cache_mode' => 'BYPASS',
        'word_count_threshold' => 10,
        'excluded_tags' => ['nav', 'footer', 'header', 'aside'],
        'remove_overlay_elements' => true,
        'remove_consent_popups' => true,
        'markdown_generator' => [
            'type' => 'DefaultMarkdownGenerator',
            'params' => [
                'content_source' => 'cleaned_html',
                'content_filter' => [
                    'type' => 'PruningContentFilter',
                    'params' => [
                        'threshold' => 0.5,
                        'threshold_type' => 'fixed',
                        'min_word_threshold' => 10,
                    ],
                ],
            ],
        ],
    ];

    public function __construct(
        #[Autowire(env: 'CRAWL4AI_BASE_URL')]
        protected string $baseUrl,
        private HttpClientInterface $client
    ) {
        parent::__construct();
    }

    protected function crawl(array $urls, string $endpoint = '/crawl', string $locale = 'en-EN', ?callable $onBatchComplete = null): array
    {
        $batches = array_chunk($urls, self::BATCH_SIZE);
        $allResults = [];
        $success = true;

        foreach ($batches as $batchIndex => $batchUrls) {
            $response = $this->client->request('POST', $this->baseUrl . $endpoint, [
                'json' => [
                    'urls' => $batchUrls,
                    'crawler_config' => array_merge(self::DEFAULT_CRAWLER_CONFIG, ['locale' => $locale]),
                ],
                'timeout' => 300,
            ]);

            $data = $response->toArray();
            $success = $success && ($data['success'] ?? false);
            $allResults = array_merge($allResults, $data['results'] ?? []);

            if ($onBatchComplete) {
                $onBatchComplete($batchIndex + 1, count($batches), count($batchUrls));
            }
        }

        return [
            'success' => $success,
            'results' => $allResults,
        ];
    }

    protected function writeOutputFile(string $content, string $sitemapUrl, string $outputFileNamePrefix, bool $fileCompression): void
    {
        $fileName = $outputFileNamePrefix . '-'
            . parse_url($sitemapUrl, PHP_URL_HOST)
            . '-' . date('Y-m-d-H-i-s')
            . '.json';

        $filesystem = new Filesystem();
        $outputDir = __DIR__ . '/../../crawl/output';

        try {
            $filesystem->mkdir($outputDir);
            $filePath = $outputDir . '/' . $fileName;
            $filesystem->dumpFile($filePath, $content);

            if ($fileCompression) {
                $gzipPath = $filePath . '.gz';
                $gzFile = gzopen($gzipPath, 'wb6');
                gzwrite($gzFile, $content);
                gzclose($gzFile);
                $filesystem->remove($filePath);
            }
        } catch (IOExceptionInterface $exception) {
            echo "An error occurred while creating file at " . $exception->getPath();
        }
    }
}
