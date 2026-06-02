<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractCrawlCommand extends Command
{
    public function __construct(
        #[Autowire(env: 'CRAWL4AI_BASE_URL')]
        protected string $baseUrl,
        private HttpClientInterface $client
    ) {
        parent::__construct();
    }

    protected function crawl(string $url, string $endpoint = '/crawl'): array
    {
        $response = $this->client->request('POST', $this->baseUrl . $endpoint, [
            'json' => ['urls' => [$url]],
        ]);

        return $response->toArray();
    }
}
