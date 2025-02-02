<?php

declare(strict_types=1);

namespace AdrienBrault\Instructrice\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class GuzzleStreamingClient implements StreamingClientInterface
{
    public function __construct(
        private readonly ClientInterface $client = new Client(),
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function request(string $method, string $url, mixed $jsonBody, array $headers = []): iterable
    {
        try {
            $response = $this->client->request(
                $method,
                $url,
                [
                    RequestOptions::JSON => $jsonBody,
                    RequestOptions::STREAM => true,
                    RequestOptions::HEADERS => $headers,
                ]
            );
        } catch (RequestException $e) {
            $this->logger->error('OpenAI Request error', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        while (! $response->getBody()->eof()) {
            $line = self::readLine($response->getBody());

            if (! str_starts_with($line, 'data:')) {
                continue;
            }

            yield trim(substr($line, \strlen('data:')));
        }
    }

    public static function readLine(StreamInterface $stream): string
    {
        $buffer = '';

        while (! $stream->eof()) {
            if ('' === ($byte = $stream->read(1))) {
                return $buffer;
            }
            $buffer .= $byte;
            if ($byte === "\n") {
                break;
            }
        }

        return $buffer;
    }
}
