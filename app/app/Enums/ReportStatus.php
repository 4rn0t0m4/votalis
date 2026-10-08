<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Open = 'open';
    case Handled = 'handled';
}
