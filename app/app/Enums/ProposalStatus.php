<?php

namespace App\Enums;

enum ProposalStatus: string
{
    case Published = 'published';
    case Hidden = 'hidden';
    /** Reformulation demandée par la modération : invisible du public, modifiable une fois par l'auteur. */
    case RewriteRequested = 'rewrite_requested';
    case Merged = 'merged';
}
