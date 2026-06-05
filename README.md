[![REMIND](https://img.shields.io/badge/REMIND-black.svg)](https://www.remind.de/) 
[![Symfony](https://img.shields.io/badge/Symfony-blue.svg)](https://symfony.com/) 
[![Crawl4Ai](https://img.shields.io/badge/Crawl4Ai-50ffff.svg)](https://docs.crawl4ai.com/) 
![Experimental](https://img.shields.io/badge/Experimental-ffd43b.svg)

# crawl4ai-php-client

Symfony CLI tool to crawl sitemaps and generate JSON/Markdown output via [Crawl4AI](https://github.com/unclecode/crawl4ai) API endpoints.

## ⚠️ Experimental Status

This project is **experimental** and under active development. The API and functionality may change without notice.

## Overview

**crawl4ai-php-client** is a Symfony-based command-line application that **exclusively provides sitemap crawling functionality**. It reads XML sitemap files, extracts URLs, crawls each URL using the Crawl4AI service, and outputs the results as JSON files with optional compression.

## Key Features

- **Sitemap-Based Web Crawling**: Extract and crawl all URLs from XML sitemap files
- **JSON Output with Optional Compression**: Results are written to JSON files with optional gzip compression
- **Symfony Console Application**: Built on Symfony 7.4 framework for robust CLI handling
- **Crawl4AI Integration**: Leverages Crawl4AI API for advanced web crawling capabilities
- **Flexible Output Modes**: 
  - Full crawl results with metadata
  - Markdown-only output (content extraction)
- **Configurable Options**:
  - Custom output file naming
  - Locale settings for crawling
  - HTTP request timeouts
  - Markdown-only extraction mode
  - Optional gzip compression
- **Docker Support**: Containerized setup with Alpine Linux base

## Requirements

- PHP >= 8.2
- Symfony 7.4
- Crawl4AI service running and accessible
- Composer for dependency management

## Installation

### Via Composer

```bash
composer install
```

### Docker

```bash
docker build -t crawl4ai-php-client .
docker run -it crawl4ai-php-client php bin/console crawl4ai:sitemap <sitemap-url>
```

## Usage

### Basic Command

```bash
php bin/console crawl4ai:sitemap <sitemap-url>
```

**Arguments:**
- `sitemapUrl` - URL to the XML sitemap file (required)

### Options

- `--outputFileNamePrefix=<prefix>` - Prefix for output JSON files (default: `crawl`)
- `--locale=<locale>` - Locale for crawling (default: `en-EN`)
- `--fileCompression` - Enable gzip compression for output files
- `--timeout=<seconds>` - HTTP request timeout in seconds (default: 300)
- `--markdownOnly` - Output only markdown content without metadata

### Examples

```bash
# Basic crawl
php bin/console crawl4ai:sitemap https://example.com/sitemap.xml

# With compression
php bin/console crawl4ai:sitemap https://example.com/sitemap.xml --fileCompression

# Markdown-only extraction with custom prefix
php bin/console crawl4ai:sitemap https://example.com/sitemap.xml \
  --outputFileNamePrefix=content \
  --markdownOnly

# Custom locale and timeout
php bin/console crawl4ai:sitemap https://example.com/sitemap.xml \
  --locale=de-DE \
  --timeout=600
```

## Output

Results are saved to the `crawl/output/` directory with timestamped filenames:

- **Format**: `{prefix}-{domain}-{timestamp}.json`
- **Example**: `crawl-example.com-2026-06-05-14-30-45.json`
- **Compression**: With `--fileCompression`, files are saved as `.json.gz` and the uncompressed version is removed

## Architecture

### Core Components

- **`AbstractCrawlCommand`** - Base class providing:
  - Crawl4AI API integration
  - HTTP client handling
  - File output management with optional compression
  - Configurable crawler defaults (locale, timeout, content filters)

- **`CrawlSitemapXmlCommand`** - Implements sitemap-specific crawling:
  - XML sitemap parsing and URL extraction
  - Command-line interface with configurable options
  - JSON output generation with timing statistics

## Environment Variables

- `CRAWL4AI_BASE_URL` - Base URL for Crawl4AI API service (required)

Example `.env` file:
```env
CRAWL4AI_BASE_URL=http://localhost:8000
```

## Directory Structure

```
.
├── bin/
│   └── console           # CLI entrypoint
├── config/               # Symfony configuration
├── crawl/
│   └── output/          # Generated JSON output files
├── public/              # Web root (if web access needed)
├── src/
│   ├── Command/         # Console commands
│   │   ├── AbstractCrawlCommand.php
│   │   └── CrawlSitemapXmlCommand.php
│   └── Kernel.php       # Symfony kernel
└── var/
    └── cache/           # Symfony cache files
```

## Limitations

- **Sitemap-only crawling**: This tool only supports XML sitemap-based URL discovery. Direct URL crawling is not supported.
- **Dependency on Crawl4AI**: Requires a running Crawl4AI service instance
- **Single-domain operation**: Each crawl targets URLs from a single sitemap file

---

**Version**: Experimental  
**Last Updated**: June 2026