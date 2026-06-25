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
    protected const DEFAULT_TIMEOUT = 300;
    protected const DEFAULT_LOCALE = 'en-EN';
    protected const DEFAULT_CRAWLER_CONFIG = [
        'cache_mode' => 'BYPASS',
        'excluded_tags' => ['nav', 'footer', 'aside', 'metadata'],
        'remove_overlay_elements' => true,
        'remove_consent_popups' => true,
        'markdown_generator' => [
            'type' => 'DefaultMarkdownGenerator',
            'params' => [
                'content_source' => 'cleaned_html',
                'options' => [
                    'ignore_links' => true,
                ],
                'content_filter' => [
                    'type' => 'PruningContentFilter',
                    'params' => [
                        'threshold' => 0.5,
                        'threshold_type' => 'fixed',
                        'min_word_threshold' => 1,
                    ],
                ],
            ],
        ],
    ];
    protected const MARKDOWN_SEPARATOR = '---';

    public function __construct(
        #[Autowire(env: 'CRAWL4AI_BASE_URL')]
        protected string $baseUrl,
        private HttpClientInterface $client
    ) {
        parent::__construct();
    }

    protected function crawl(
        array $urls,
        string $endpoint = '/crawl',
        string $locale = self::DEFAULT_LOCALE,
        int $timeout = self::DEFAULT_TIMEOUT,
        bool $markdownOnly = false,
        ?string $markdownTitle = null,
        array $excludeUrls = [],
        string $excludeCssSelectors = '',
    ): array|string {
        $results = $markdownOnly ? '' . PHP_EOL : [];

        if ($markdownOnly && $markdownTitle) {
            $results .= '# ' . $markdownTitle . PHP_EOL . PHP_EOL;
        }
        foreach ($urls as $url) {
            if (in_array($url, $excludeUrls, true)) {
                echo 'Skipping excluded URL: ' . $url . PHP_EOL;
                continue;
            }
            $response = $this->client->request('POST', $this->baseUrl . $endpoint, [
                'json' => [
                    'urls' => [$url],
                    'crawler_config' => array_merge(self::DEFAULT_CRAWLER_CONFIG, ['locale' => $locale, 'excluded_selector' => $excludeCssSelectors]),
                ],
                'timeout' => $timeout,
            ]);
            $data = $response->toArray();

            if ($markdownOnly) {
                $results .= self::MARKDOWN_SEPARATOR . PHP_EOL;

                if (($data['results'][0]['metadata']['title'] ?? '') !== '') {
                    $results .= 'title: ' . $data['results'][0]['metadata']['title'] . PHP_EOL;
                }

                if (($data['results'][0]['metadata']['description'] ?? '') !== '') {
                    $results .= 'description: ' . $data['results'][0]['metadata']['description'] . PHP_EOL;
                }

                $results .= $data['results'][0]['markdown']['fit_markdown'] ?? '';
                $results .= PHP_EOL;
                continue;
            }

            $results[] = $data;
        }

        return $results;
    }

    protected function writeOutputFile(
        string $content,
        string $sitemapUrl,
        string $outputFileNamePrefix,
        bool $fileCompression,
        bool $markdownOnly = false
    ): void {
        $fileName = $outputFileNamePrefix . '-'
            . parse_url($sitemapUrl, PHP_URL_HOST)
            . '-' . date('Y-m-d-H-i-s')
            . '.'
            . ($markdownOnly ? 'md' : 'json');

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
            echo 'An error occurred while creating file at ' . $exception->getPath();
        }
    }
}
