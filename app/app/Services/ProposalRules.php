<?php

namespace App\Services;

use Illuminate\Validation\Validator;

/**
 * Règles du format imposé de la fiche de proposition (CDC 4.2), partagées par le
 * formulaire, la modification et l'import d'amorçage : une seule source de vérité.
 */
final class ProposalRules
{
    public const TITLE_MAX = 120;

    public const PROBLEM_MAX = 500;

    public const MEASURE_MAX = 1500;

    public const COST_MAX = 300;

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'theme_id' => ['required', 'integer', 'exists:themes,id'],
            'title' => ['required', 'string', 'max:'.self::TITLE_MAX],
            'problem' => ['required', 'string', 'max:'.self::PROBLEM_MAX],
            'measure' => ['required', 'string', 'max:'.self::MEASURE_MAX],
            'cost_unknown' => ['boolean'],
            'cost_estimate' => ['nullable', 'string', 'max:'.self::COST_MAX, 'required_unless:cost_unknown,true,1'],
            'sources' => ['array', 'max:10'],
            'sources.*' => ['nullable', 'string', 'url:http,https', 'max:500'],
            'personal_source' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'title.required' => 'Le titre est obligatoire : formulez la mesure avec un verbe d’action.',
            'title.max' => 'Le titre ne doit pas dépasser :max caractères.',
            'problem.required' => 'Décrivez le problème visé par la mesure.',
            'problem.max' => 'Le problème visé ne doit pas dépasser :max caractères.',
            'measure.required' => 'Décrivez la mesure proposée.',
            'measure.max' => 'La mesure proposée ne doit pas dépasser :max caractères.',
            'cost_estimate.required_unless' => 'Indiquez un coût ou un impact estimé, ou cochez « inconnu ».',
            'cost_estimate.max' => 'Le coût ou impact estimé ne doit pas dépasser :max caractères.',
            'theme_id.required' => 'Choisissez un thème.',
            'theme_id.exists' => 'Ce thème n’existe pas.',
            'sources.*.url' => 'Chaque source doit être une adresse web complète (https://…).',
            'sources.max' => 'Dix sources au maximum.',
        ];
    }

    /** Au moins une URL, ou la mention « proposition personnelle ». */
    public static function afterSources(Validator $validator): void
    {
        $data = $validator->getData();
        $urls = array_filter(array_map(fn ($u) => is_string($u) ? trim($u) : '', $data['sources'] ?? []));
        $personal = filter_var($data['personal_source'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($urls === [] && ! $personal) {
            $validator->errors()->add('sources', 'Indiquez au moins une source (adresse web) ou cochez « proposition personnelle ».');
        }
    }

    /**
     * Normalise les entrées d'un formulaire ou d'une ligne d'import.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        $sources = array_values(array_filter(array_map(fn ($u) => is_string($u) ? trim($u) : '', (array) ($input['sources'] ?? [])), fn (string $u) => $u !== ''));
        $costUnknown = filter_var($input['cost_unknown'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return [
            'theme_id' => isset($input['theme_id']) && $input['theme_id'] !== '' ? (int) $input['theme_id'] : null,
            'title' => trim((string) ($input['title'] ?? '')),
            'problem' => trim((string) ($input['problem'] ?? '')),
            'measure' => trim((string) ($input['measure'] ?? '')),
            'cost_unknown' => $costUnknown,
            'cost_estimate' => $costUnknown ? null : (trim((string) ($input['cost_estimate'] ?? '')) ?: null),
            'sources' => $sources,
            'personal_source' => filter_var($input['personal_source'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }
}
