<?php

namespace App\Enums;

enum ProposalStatus: string
{
    case Published = 'published';
    case Hidden = 'hidden';
    case Merged = 'merged';
}
