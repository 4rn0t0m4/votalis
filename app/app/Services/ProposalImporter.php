<?php

namespace App\Services;

use App\Enums\ProposalOrigin;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use SplFileObject;

/**
 * Import d'un jeu d'amorçage (CSV UTF-8, séparateur « ; »). Transactionnel : une ligne
 * invalide annule tout et le rapport nomme la ligne et le champ fautifs.
 */
class ProposalImporter
{
    public const COLUMNS = ['theme_slug', 'title', 'problem', 'measure', 'cost_estimate', 'cost_unknown', 'source_urls', 'seed_source'];

    public function __construct(private readonly ProposalService $proposals) {}

    public function import(string $path, bool $dryRun = false): ImportReport
    {
        $report = new ImportReport;
        $rows = $this->read($path, $report);

        if ($report->hasErrors()) {
            return $report;
        }

        $themes = Theme::query()->get()->keyBy('slug');

        DB::beginTransaction();

        try {
            foreach ($rows as $line => $row) {
                $theme = $themes->get($row['theme_slug']);

                if ($theme === null || $theme->isArchived()) {
                    $report->addError($line, 'theme_slug', $theme === null ? "Thème inconnu : « {$row['theme_slug']} »." : 'Thème archivé.');

                    continue;
                }

                try {
                    $this->proposals->create([
                        'theme_id' => $theme->id,
                        'title' => $row['title'],
                        'problem' => $row['problem'],
                        'measure' => $row['measure'],
                        'cost_estimate' => $row['cost_estimate'],
                        'cost_unknown' => in_array(mb_strtolower(trim($row['cost_unknown'])), ['1', 'oui', 'true', 'vrai', 'x'], true),
                        'sources' => array_filter(array_map('trim', explode('|', $row['source_urls']))),
                        'personal_source' => false,
                    ], null, ProposalOrigin::Seed, trim($row['seed_source']) ?: null);

                    $report->imported++;
                } catch (ValidationException $e) {
                    foreach ($e->errors() as $field => $messages) {
                        $report->addError($line, $field, implode(' ', $messages));
                    }
                }
            }

            if ($dryRun || $report->hasErrors()) {
                DB::rollBack();
                $report->written = false;
            } else {
                DB::commit();
                $report->written = true;
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $report;
    }

    /**
     * @return array<int, array<string, string>> Lignes indexées par numéro de ligne du fichier
     */
    private function read(string $path, ImportReport $report): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Fichier introuvable ou illisible : {$path}");
        }

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl(';');

        $header = null;
        $rows = [];

        foreach ($file as $index => $fields) {
            if (! is_array($fields) || $fields === [null]) {
                continue;
            }

            $line = $index + 1;

            if ($header === null) {
                $header = array_map(fn ($h) => mb_strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $fields);

                if ($header !== self::COLUMNS) {
                    $report->addError($line, 'en-tête', 'Colonnes attendues : '.implode(';', self::COLUMNS).'.');

                    return [];
                }

                continue;
            }

            if (count($fields) !== count(self::COLUMNS)) {
                $report->addError($line, 'ligne', count($fields).' colonnes au lieu de '.count(self::COLUMNS).'.');

                continue;
            }

            $rows[$line] = array_combine(self::COLUMNS, array_map(fn ($v) => (string) $v, $fields));
        }

        if ($header === null) {
            $report->addError(1, 'fichier', 'Fichier vide.');
        }

        return $rows;
    }
}
