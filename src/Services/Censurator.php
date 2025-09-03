<?php

namespace App\Services;

class Censurator
{
    private array $banword = [];

    public function __construct(string $projectDir)
    {
        $this->bannedWordsFile = $projectDir . '/data/banned_words.txt';
        $this->loadBannedWords();
    }

    private function loadBannedWords(): void
    {
        if (file_exists($this->bannedWordsFile)) {
            $content = file_get_contents($this->bannedWordsFile);
            $this->banword = array_filter(
                array_map('trim', explode("\n", $content ?: '')),
                fn($word) => $word && !str_starts_with($word, '#')
            );
        }
    }

    public function purify(string $string): string {
        foreach ($this->banword as $word) {
            $replacement = str_repeat('*', strlen($word));
            $string = str_ireplace($word, $replacement, $string);
        }

        return $string;
    }

}