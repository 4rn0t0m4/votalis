<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Pictogrammes en ligne (tracés neutres, aucune ressource externe, aucun style en ligne).
 * Décoratifs par défaut (`aria-hidden`) ; passer `label` pour un pictogramme porteur de sens.
 */
class Icon extends Component
{
    /** @var array<string, string> Tracés sur une grille 24 × 24, trait de 2,2 px. */
    public const PATHS = [
        'check' => '<path d="M5 13l4 4L19 7"/>',
        'cross' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'question' => '<path d="M9 9a3 3 0 1 1 4.5 2.6c-.9.5-1.5 1.4-1.5 2.4M12 18h.01"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'chevron-down' => '<path d="M6 9l6 6 6-6"/>',
        'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'lock' => '<rect x="4" y="11" width="16" height="10" rx="3"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'scale' => '<path d="M12 3v18M3 7l9-4 9 4M5 7l-2 7h6L7 7M19 7l-2 7h6l-2-7"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
        'star' => '<path d="M12 3l2.4 5.2 5.6.7-4.1 3.9 1.1 5.6L12 15.7l-5 2.7 1.1-5.6L4 8.9l5.6-.7z"/>',
        'book' => '<path d="M4 19V5a2 2 0 0 1 2-2h12v18H6a2 2 0 0 1-2-2zM8 7h8M8 11h8"/>',
        'refresh' => '<path d="M3 12a9 9 0 0 1 15.5-6.3M21 12a9 9 0 0 1-15.5 6.3M18 3v4h-4M6 21v-4h4"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        'sparkle' => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6.5 6.5l2.5 2.5M15 15l2.5 2.5M6.5 17.5L9 15M15 9l2.5-2.5"/>',
        'flag' => '<path d="M5 21V4M5 4h12l-2 4 2 4H5"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>',
        'chart' => '<path d="M4 20h16M7 16v-5M12 16V8M17 16v-8"/>',
        'pen' => '<path d="M4 20l4-1 10-10-3-3L5 16zM13 7l3 3"/>',
        'compass' => '<circle cx="12" cy="12" r="9"/><path d="M15 9l-2 5-5 2 2-5z"/>',
        'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'hand' => '<path d="M8 13V5a1.5 1.5 0 0 1 3 0v6M11 11V4a1.5 1.5 0 0 1 3 0v7M14 11V6a1.5 1.5 0 0 1 3 0v8a6 6 0 0 1-6 6h-1a6 6 0 0 1-5.2-3L3.5 14a1.5 1.5 0 0 1 2.4-1.8L8 14"/>',
        'layers' => '<path d="M12 4l9 5-9 5-9-5zM3 14l9 5 9-5"/>',
        'bolt' => '<path d="M13 3L5 14h6l-1 7 8-11h-6z"/>',
        'coins' => '<circle cx="9" cy="9" r="6"/><path d="M14.5 8.5a6 6 0 1 1-6 6M6 9h6M9 6v6"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="3"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>',
        'pulse' => '<path d="M3 12h4l2-6 4 12 2-6h6"/>',
        'graduation' => '<path d="M2 9l10-5 10 5-10 5zM6 11v5c0 1.5 3 3 6 3s6-1.5 6-3v-5M22 9v6"/>',
        'home' => '<path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/>',
        'leaf' => '<path d="M5 20c0-9 5-15 15-15 0 10-6 15-15 15zM5 20c3-5 6-8 10-10"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
        'gavel' => '<path d="M14 4l6 6M12 6l6 6M3 21l8-8M9 9l4-4 6 6-4 4z"/>',
        'bus' => '<rect x="4" y="4" width="16" height="14" rx="3"/><path d="M4 11h16M8 18v2M16 18v2M8 15h.01M16 15h.01"/>',
        'cpu' => '<rect x="6" y="6" width="12" height="12" rx="2"/><rect x="9" y="9" width="6" height="6" rx="1"/><path d="M9 2v4M15 2v4M9 18v4M15 18v4M2 9h4M2 15h4M18 9h4M18 15h4"/>',
        'landmark' => '<path d="M3 21h18M5 21V10M9 21V10M15 21V10M19 21V10M2 10l10-6 10 6z"/>',
        'map' => '<path d="M9 4L3 6v14l6-2 6 2 6-2V4l-6 2zM9 4v14M15 6v14"/>',
        'wheat' => '<path d="M12 21V8M12 8c-3 0-5-2-5-5 3 0 5 2 5 5zM12 8c3 0 5-2 5-5-3 0-5 2-5 5zM12 13c-3 0-5-2-5-5 3 0 5 2 5 5zM12 13c3 0 5-2 5-5-3 0-5 2-5 5z"/>',
        'drop' => '<path d="M12 3s6 7 6 11a6 6 0 0 1-12 0c0-4 6-11 6-11z"/>',
    ];

    public function __construct(public string $name, public ?string $label = null, public string $stroke = '2.2') {}

    public function render(): View
    {
        return view('components.icon', ['paths' => self::PATHS[$this->name] ?? self::PATHS['sparkle']]);
    }
}
