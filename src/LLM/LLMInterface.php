<?php

declare(strict_types=1);

namespace AdrienBrault\Instructrice\LLM;

interface LLMInterface
{
    /**
     * @param array<array-key, mixed>            $schema
     * @param callable(mixed, string): void|null $onChunk
     */
    public function get(
        array $schema,
        string $context,
        ?callable $onChunk = null,
    ): mixed;
}
