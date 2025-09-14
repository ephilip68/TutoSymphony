<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('duration', [$this, 'formatDuration']),
        ];
    }

    public function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return $hours . ' h' . ($mins > 0 ? ' ' . $mins . ' min' : '');
    }
}
