<?php

namespace App\Services;

class Censurator
{
    private array $banword = [];
    private string $bannedWordsFile;

    public function __construct(string $projectDir)
    {
        $this->bannedWordsFile = $projectDir . '/data/banned_words.txt';
        $this->loadBannedWords();
    }

    private function loadBannedWords(): void
    {
        if (file_exists($this->bannedWordsFile)) {
            $content = file_get_contents($this->bannedWordsFile);
            if ($content !== false) {
                $words = explode("\n", $content);
                $this->banword = array_filter(array_map('trim', $words), function($word) {
                    return !empty($word) && !str_starts_with($word, '#');
                });
            }
        }
    }

    public function purify(string $string): string {
        $lowerString = strtolower($string);

        // Parcourir chaque mot interdit
        foreach ($this->banword as $word) {
            $wordLower = strtolower($word);
            $stringLower = strtolower($string);

            $pos = strpos($stringLower, $wordLower);

            while ($pos !== false) {
                $replacement = str_repeat('*', strlen($word));
                $string = substr_replace($string, $replacement, $pos, strlen($word));

                $stringLower = strtolower($string);

                $pos = strpos($stringLower, $wordLower, $pos + strlen($word));
            }
        }

        return $string;
    }

}