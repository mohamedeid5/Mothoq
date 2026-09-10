<?php

namespace App;

enum ServiceCenterStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Suspended = 'suspended';
}
