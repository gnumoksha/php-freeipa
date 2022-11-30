<?php

declare(strict_types=1);

namespace Gnumoksha\FreeIpa\Model\Group;

class Group
{
    public $cn;
    public $description;
    public $gidnumber;
    public $member;
    public $memberof;
    public $memberindirect;
    public $memberofindirect;
    public $ipaexternalmember;
}
