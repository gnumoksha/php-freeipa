<?php

declare(strict_types=1);

namespace Gnumoksha\FreeIpa\Model\Group;

final class Group
{
    public string $cn;
    public string $description;
    public int $gidnumber;
    public array $member;
    public array $memberof;
    public array $memberindirect;
    public array $memberofindirect;
    public array $ipaexternalmember;
}
