<?php

use Illuminate\Support\Facades\Schedule;

// Signaux d'intégrité : chaque nuit, présentés aux modérateurs, jamais appliqués (CDC section 7).
Schedule::command('integrity:scan')->dailyAt('04:30')->withoutOverlapping();

// Rapport de transparence du trimestre écoulé, le premier jour de chaque trimestre (CDC section 6).
Schedule::command('transparency:report')->quarterly()->at('06:00');

// Conservation (CDC section 8) : préavis puis suppression des comptes inactifs, purge des jetons expirés.
Schedule::command('accounts:purge-inactive')->dailyAt('05:15')->withoutOverlapping();
Schedule::command('auth:clear-resets')->daily();
