<?php

namespace App\Support;

use App\Models\Argument;
use App\Models\Proposal;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** Correspondance entre les slugs d'URL en français et les types de contenu modérables. */
final class ModerationTarget
{
    private const SLUGS = ['proposition' => Proposal::class, 'argument' => Argument::class];

    public static function resolve(string $slug, int $id): Proposal|Argument
    {
        $class = self::SLUGS[$slug] ?? throw new NotFoundHttpException;

        /** @var Proposal|Argument */
        return $class::query()->findOrFail($id);
    }

    public static function slugFor(Proposal|Argument $target): string
    {
        return $target instanceof Proposal ? 'proposition' : 'argument';
    }

    /** Depuis une entrée du journal ou un signalement (`proposal` / `argument`). */
    public static function slugForType(string $morphType): string
    {
        return $morphType === 'proposal' ? 'proposition' : 'argument';
    }
}
