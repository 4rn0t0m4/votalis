<?php

namespace App\Enums;

/** Issue d'un calcul de consensus (lot 7). */
enum ConsensusRunStatus: string
{
    /** Seuils d'activation non atteints : aucun appel au service. */
    case Inactive = 'inactive';
    /** Le service n'a pas trouvé au moins deux familles de votants. */
    case Insufficient = 'insufficient';
    case Computed = 'computed';
    /** Service indisponible ou réponse invalide : les scores précédents restent servis. */
    case Failed = 'failed';
}
